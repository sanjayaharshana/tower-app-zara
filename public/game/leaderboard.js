/* ZARA Tower Challenge — live leaderboard for a 1080×1920 portrait kiosk screen.
   Polls KIOSK.dataUrl (LeaderboardScreenController::data) and animates whatever changed. */
(() => {
  'use strict';

  const K = window.KIOSK;
  const W = 1080, H = 1920;
  const LIST_ROWS = 7, ROW_STEP = 72; // places 4–10, keep ROW_STEP in sync with --row-step in leaderboard.css
  const TOAST_MS = 4200;

  const $ = (id) => document.getElementById(id);
  const fmt = (n) => Number(n).toLocaleString('en-US');
  const packs = (n) => n + (n === 1 ? ' pack' : ' packs');
  const el = {
    stage: $('stage'), live: $('live'), liveText: $('live-text'),
    players: $('stat-players'), games: $('stat-games'),
    podium: $('podium'), list: $('list'), ticker: $('ticker'),
    qr: $('qr'), qrCard: $('qr-card'), ctaTitle: $('cta-title'), ctaSub: $('cta-sub'), ctaUrl: $('cta-url'),
    toast: $('toast'), toastHead: $('toast-head'), toastFace: $('toast-face'), toastName: $('toast-name'),
    toastScore: $('toast-score'), toastPacks: $('toast-packs'),
    champion: $('champion'), championName: $('champion-name'), confetti: $('confetti'),
  };

  // ───────────── Stage ─────────────
  // the design is a fixed 1080×1920 stage, scaled to fit whatever screen it's on
  function fit() {
    const s = Math.min(window.innerWidth / W, window.innerHeight / H);
    el.stage.style.transform = `translate(-50%, -50%) scale(${s})`;
  }

  function splitTitle() {
    let i = 0;
    for (const part of document.querySelectorAll('.title > span')) {
      const text = part.textContent;
      part.textContent = '';
      for (const ch of text) {
        const s = document.createElement('span');
        s.className = 'ch';
        s.textContent = ch;
        s.style.setProperty('--i', i++);
        part.appendChild(s);
      }
    }
  }

  // ───────────── Helpers ─────────────
  const COLORS = ['#e4252b', '#2c9a3c', '#7a3db0', '#1f57c8', '#d9860b', '#8a4a22', '#d0457a', '#138a8a'];
  function colorFor(name) {
    let h = 0;
    for (const ch of name) h = (h * 31 + ch.codePointAt(0)) | 0;
    return COLORS[Math.abs(h) % COLORS.length];
  }
  function setFace(face, name) {
    const first = [...name.trim()][0];
    face.textContent = first ? first.toUpperCase() : '?';
    face.style.setProperty('--c', colorFor(name));
  }

  const clamp01 = (v) => Math.max(0, Math.min(1, v));

  // numbers count up from what they showed to their new value
  const counting = new WeakMap();
  function countTo(node, to, ms = 1200, delay = 0) {
    const from = Number(node.dataset.val || 0);
    node.dataset.val = to;
    cancelAnimationFrame(counting.get(node));
    if (from === to || ms === 0) { node.textContent = fmt(to); return; }
    node.textContent = fmt(from);
    const t0 = performance.now() + delay;
    const step = (now) => {
      const p = clamp01((now - t0) / ms);
      node.textContent = fmt(Math.round(from + (to - from) * (1 - (1 - p) ** 3)));
      if (p < 1) counting.set(node, requestAnimationFrame(step));
    };
    counting.set(node, requestAnimationFrame(step));
  }

  function restart(node, cls) {
    node.classList.remove(cls);
    void node.offsetWidth;
    node.classList.add(cls);
  }

  // ───────────── Podium (1–3) ─────────────
  const SPOT_X = { 1: 540, 2: 211, 3: 869 }; // podium column centres on the stage
  function renderPodium(board, prevRanks, first) {
    for (const rank of [1, 2, 3]) {
      const spot = el.podium.querySelector(`.spot--${rank}`);
      const q = (sel) => spot.querySelector(sel);
      const p = board[rank - 1];
      const id = p ? String(p.id) : '';
      const newcomer = spot.dataset.id !== id;
      const improved = !newcomer && p && p.score > Number(q('.score').dataset.val || 0);
      spot.dataset.id = id;
      spot.classList.toggle('empty', !p);
      if (!p) {
        q('.face').textContent = '?';
        q('.face').style.removeProperty('--c');
        q('.name').textContent = 'Your name?';
        q('.score').textContent = '0'; // hidden by .empty, keeps the spot's height
        delete q('.score').dataset.val;
        q('.packs').textContent = 'Play now!';
        continue;
      }
      setFace(q('.face'), p.name);
      q('.name').textContent = p.name;
      q('.packs').textContent = packs(p.height);
      if (newcomer) q('.score').dataset.val = 0;
      countTo(q('.score'), p.score, first ? 1600 : 1400, first ? 1300 : 0);
      if (!first && (newcomer || improved)) {
        restart(spot, 'pop');
        // a smaller party for moving up within the podium; a new arrival gets the full burst
        burst(SPOT_X[rank], 640, prevRanks.has(p.id) && prevRanks.get(p.id) <= 3 ? 40 : 90, rank === 1 ? 'gold' : 'party');
      }
    }
  }

  // ───────────── Places 4–10 ─────────────
  const rows = new Map(); // player id -> row
  const ghosts = new Map(); // empty place index -> row
  const hotTimers = new WeakMap();

  function makeRow(cls) {
    const row = document.createElement('div');
    row.className = cls;
    row.innerHTML = '<span class="rank"></span><span class="face"></span><span class="row-name"></span><span class="row-packs"></span><span class="row-score"></span>';
    return row;
  }

  function renderList(board, prevRanks, first) {
    const entries = board.slice(3, 3 + LIST_ROWS);
    const seen = new Set();
    entries.forEach((p, i) => {
      let row = rows.get(p.id);
      const isNew = !row;
      if (isNew) {
        row = makeRow(first ? 'row' : 'row live-in');
        row.style.setProperty('--i', i);
        row.style.transform = `translateY(${i * ROW_STEP}px)`;
        el.list.appendChild(row);
        rows.set(p.id, row);
      }
      const scoreNode = row.querySelector('.row-score');
      const oldScore = Number(scoreNode.dataset.val || 0);
      row.style.transform = `translateY(${i * ROW_STEP}px)`;
      row.querySelector('.rank').textContent = p.rank;
      setFace(row.querySelector('.face'), p.name);
      row.querySelector('.row-name').textContent = p.name;
      row.querySelector('.row-packs').textContent = packs(p.height);
      countTo(scoreNode, p.score, 1200, first ? 1600 + i * 90 : 0);
      seen.add(p.id);

      if (first) return;
      const was = prevRanks.get(p.id);
      if (was === undefined) markHot(row, 'NEW', true);
      else if (p.rank < was) markHot(row, '▲ ' + (was - p.rank), false);
      else if (p.score > oldScore) markHot(row, null, false);
    });

    for (const [id, row] of rows) {
      if (seen.has(id)) continue;
      rows.delete(id);
      row.classList.add('leave');
      setTimeout(() => row.remove(), 600);
    }

    // dashed "could be you" rows keep the list full until ten players are on the board
    for (let i = 0; i < LIST_ROWS; i++) {
      const needed = i >= entries.length;
      let ghost = ghosts.get(i);
      if (needed && !ghost) {
        ghost = makeRow('row ghost');
        ghost.style.setProperty('--i', i);
        ghost.style.transform = `translateY(${i * ROW_STEP}px)`;
        ghost.querySelector('.rank').textContent = i + 4;
        ghost.querySelector('.face').remove();
        ghost.querySelector('.row-name').textContent = 'This spot could be yours!';
        el.list.prepend(ghost);
        ghosts.set(i, ghost);
      } else if (!needed && ghost) {
        ghost.remove();
        ghosts.delete(i);
      }
    }
  }

  function markHot(row, label, isNew) {
    clearTimeout(hotTimers.get(row));
    row.querySelector('.move')?.remove();
    restart(row, 'hot');
    if (label) {
      const badge = document.createElement('span');
      badge.className = 'move' + (isNew ? ' new' : '');
      badge.textContent = label;
      row.insertBefore(badge, row.querySelector('.row-score'));
    }
    hotTimers.set(row, setTimeout(() => {
      row.classList.remove('hot');
      row.querySelector('.move')?.remove();
    }, 7000));
  }

  // ───────────── Ticker ─────────────
  let tickerKey = null;
  function renderTicker(recent) {
    const key = recent.map((r) => r.id + ':' + (r.rank || '')).join(',');
    if (key === tickerKey) return;
    tickerKey = key;
    const run = el.ticker;
    run.textContent = '';
    if (!recent.length) {
      run.classList.add('still');
      const empty = document.createElement('span');
      empty.className = 'tick-empty';
      empty.textContent = 'No games yet. Scan the code and be the first!';
      run.appendChild(empty);
      return;
    }
    run.classList.remove('still');
    // one copy at least as wide as the track, then a second copy so the -50% loop is seamless
    const copy = document.createElement('span');
    copy.style.display = 'inline-flex';
    run.appendChild(copy);
    const trackW = run.parentElement.offsetWidth;
    do { for (const r of recent) copy.append(...tickItem(r)); } while (copy.offsetWidth < trackW);
    run.appendChild(copy.cloneNode(true));
    run.style.setProperty('--dur', (copy.offsetWidth / 95).toFixed(1) + 's');
    restart(run, 'ticker-run');
  }

  function tickItem(r) {
    const item = document.createElement('span');
    item.className = 'tick';
    const name = document.createElement('span');
    name.textContent = r.name;
    const score = document.createElement('b');
    score.textContent = fmt(r.score);
    const height = document.createElement('small');
    height.textContent = packs(r.height);
    item.append(name, score, height);
    if (r.rank) {
      const tag = document.createElement('span');
      tag.className = 'tag';
      tag.textContent = '#' + r.rank;
      item.append(tag);
    }
    const sep = document.createElement('i');
    sep.className = 'tick-sep';
    return [item, sep];
  }

  // ───────────── Footer ─────────────
  function drawQr() {
    el.ctaUrl.textContent = K.playUrl.replace(/^https?:\/\//, '').replace(/\/$/, '');
    if (typeof window.qrcode !== 'function') { el.qr.classList.add('qr--missing'); return; } // CDN unreachable
    const qr = window.qrcode(0, 'M');
    qr.addData(K.playUrl);
    qr.make();
    el.qr.innerHTML = qr.createSvgTag(4, 0);
  }

  function renderCta(data) {
    el.qrCard.classList.toggle('closed', !data.open);
    el.ctaTitle.textContent = data.open ? 'Scan to play!' : data.closed_title;
    el.ctaSub.textContent = data.open ? 'Stack the packs. Top the board.' : 'Check back soon!';
  }

  // ───────────── Live moments ─────────────
  const seenPlays = new Set();
  const toasts = [];
  let toastBusy = false;

  function queueToast(play) {
    toasts.push(play);
    while (toasts.length > 3) toasts.shift(); // a busy moment: show the latest few
    if (!toastBusy) nextToast();
  }

  function nextToast() {
    const play = toasts.shift();
    if (!play) { toastBusy = false; return; }
    toastBusy = true;
    el.toastHead.textContent = play.rank ? `#${play.rank} on the board!` : 'Just played!';
    setFace(el.toastFace, play.name);
    el.toastName.textContent = play.name;
    el.toastScore.dataset.val = 0;
    countTo(el.toastScore, play.score, 1300, 200);
    el.toastPacks.textContent = packs(play.height) + ' stacked';
    el.toast.classList.add('show');
    burst(540, 1120, 60, 'party');
    setTimeout(() => {
      el.toast.classList.remove('show');
      setTimeout(nextToast, 700);
    }, TOAST_MS);
  }

  function showChampion(p) {
    el.championName.textContent = p.name;
    el.champion.classList.add('show');
    burst(540, 820, 160, 'gold');
    rain(140);
    setTimeout(() => el.champion.classList.remove('show'), 3800);
  }

  function celebrate(before, now) {
    // newly finished rounds, oldest first
    const fresh = now.recent.filter((r) => !seenPlays.has(r.id)).reverse();
    for (const r of fresh) { seenPlays.add(r.id); queueToast(r); }
    const was = before.leaderboard[0], is = now.leaderboard[0];
    if (is && (!was || was.id !== is.id)) setTimeout(() => showChampion(is), 500);
  }

  // ───────────── Confetti ─────────────
  const cctx = el.confetti.getContext('2d');
  const PALETTES = {
    party: ['#ffd23f', '#e4252b', '#ffffff', '#2c9a3c', '#1f57c8', '#7a3db0', '#f4b52e'],
    gold: ['#ffd23f', '#f4b52e', '#fff3b0', '#ffffff', '#e4252b'],
  };
  let bits = [], confettiRaf = 0, confettiT = 0;

  function addBit(x, y, vx, vy, colors) {
    bits.push({
      x, y, vx, vy, rot: Math.random() * 6, vr: (Math.random() - 0.5) * 14,
      w: 12 + Math.random() * 12, h: 7 + Math.random() * 7, c: colors[(Math.random() * colors.length) | 0],
      age: 0, life: 2.4 + Math.random() * 1.4, flip: Math.random() * 6,
    });
  }
  function burst(x, y, n, palette) {
    for (let i = 0; i < n; i++) {
      const a = -Math.PI / 2 + (Math.random() - 0.5) * Math.PI * 1.25;
      const v = 500 + Math.random() * 900;
      addBit(x, y, Math.cos(a) * v, Math.sin(a) * v, PALETTES[palette]);
    }
    startConfetti();
  }
  function rain(n) {
    for (let i = 0; i < n; i++) addBit(Math.random() * W, -20 - Math.random() * 400, (Math.random() - 0.5) * 120, 200 + Math.random() * 300, PALETTES.gold);
    startConfetti();
  }
  function startConfetti() {
    if (confettiRaf) return;
    confettiT = performance.now();
    confettiRaf = requestAnimationFrame(confettiFrame);
  }
  function confettiFrame(now) {
    const dt = Math.min(0.05, (now - confettiT) / 1000);
    confettiT = now;
    const drag = Math.pow(0.2, dt);
    cctx.clearRect(0, 0, W, H);
    for (const b of bits) {
      b.age += dt;
      b.vx *= drag;
      b.vy = b.vy * drag + 1500 * dt;
      b.x += b.vx * dt + Math.sin(b.age * 6 + b.flip) * 40 * dt;
      b.y += b.vy * dt;
      b.rot += b.vr * dt;
      cctx.save();
      cctx.globalAlpha = clamp01((b.life - b.age) / 0.6);
      cctx.translate(b.x, b.y);
      cctx.rotate(b.rot);
      cctx.scale(1, Math.cos(b.age * 9 + b.flip));
      cctx.fillStyle = b.c;
      cctx.fillRect(-b.w / 2, -b.h / 2, b.w, b.h);
      cctx.restore();
    }
    bits = bits.filter((b) => b.age < b.life && b.y < H + 40);
    if (bits.length) confettiRaf = requestAnimationFrame(confettiFrame);
    else { confettiRaf = 0; cctx.clearRect(0, 0, W, H); }
  }

  // ───────────── Data ─────────────
  let prev = null, prevText = '', failures = 0;

  function render(data) {
    const first = prev === null;
    const prevRanks = new Map((prev ? prev.leaderboard : []).map((p) => [p.id, p.rank]));
    countTo(el.players, data.stats.players, first ? 1400 : 900, first ? 600 : 0);
    countTo(el.games, data.stats.games_today, first ? 1400 : 900, first ? 700 : 0);
    renderPodium(data.leaderboard, prevRanks, first);
    renderList(data.leaderboard, prevRanks, first);
    renderTicker(data.recent);
    renderCta(data);
    if (first) data.recent.forEach((r) => seenPlays.add(r.id));
    else celebrate(prev, data);
    prev = data;
  }

  function setLive(ok) {
    el.live.classList.toggle('off', !ok);
    el.liveText.textContent = ok ? 'LIVE' : 'RECONNECTING…';
  }

  async function poll() {
    const ctrl = new AbortController();
    const timer = setTimeout(() => ctrl.abort(), 8000);
    try {
      const res = await fetch(K.dataUrl, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: ctrl.signal });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const text = await res.text();
      failures = 0;
      setLive(true);
      if (text !== prevText) { prevText = text; render(JSON.parse(text)); }
    } catch {
      if (++failures >= 2) setLive(false); // keep showing the last board
    } finally {
      clearTimeout(timer);
      setTimeout(poll, K.pollMs);
    }
  }

  // ───────────── Kiosk ─────────────
  // hide the pointer when nobody's using a mouse; double-click toggles full screen
  let idleTimer = 0;
  document.addEventListener('mousemove', () => {
    document.body.classList.remove('idle');
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => document.body.classList.add('idle'), 3000);
  });
  document.addEventListener('dblclick', () => {
    if (document.fullscreenElement) document.exitFullscreen();
    else document.documentElement.requestFullscreen?.().catch(() => {});
  });
  // a fresh page every few hours picks up new releases; only when the server is reachable
  setInterval(() => { if (failures === 0) location.reload(); }, 6 * 60 * 60 * 1000);
  // now and then, a little sparkle over the champion
  setInterval(() => { if (prev && prev.leaderboard.length && !toastBusy) burst(540, 600, 26, 'gold'); }, 40000);

  window.addEventListener('resize', fit);
  fit();
  splitTitle();
  drawQr();
  poll();
  setTimeout(() => document.body.classList.add('idle'), 3000);
})();
