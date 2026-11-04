/* ZARA Tower Challenge — drop the moving packs and stack them as high as you can before time runs out. */
(() => {
  'use strict';

  // ───────────────────────── Config ─────────────────────────
  const CONFIG = {
    GAME_TIME: 30,          // seconds per round
    PERFECT_BONUS: 200,     // extra points for a perfect stack
    PERFECT_TIME_BONUS: 1,  // seconds added for a perfect stack
    MISS_PENALTY: 3,        // seconds lost when a pack falls off
    // mobile numbers accepted on the start screen (spaces/dashes are stripped first);
    // Sri Lankan mobiles such as 0771234567, +94771234567 or 94771234567.
    // Keep in sync with Player::PHONE_PATTERN on the server.
    PHONE_PATTERN: /^(?:\+94|0094|94|0)7\d{8}$/,
  };

  // Asset base and API endpoints, injected by resources/views/game.blade.php
  const SERVER = window.ZARA;

  // Pack photos used for stacking.
  const PACKS = [
    { name: 'Zara Cafe', src: SERVER.assetBase + 'assets/packs/cafe.webp' },
    { name: 'Zara Cardamom Milk Tea', src: SERVER.assetBase + 'assets/packs/cardamom-milk-tea.webp' },
    { name: 'Zara Choco', src: SERVER.assetBase + 'assets/packs/choco.webp' },
    { name: 'Zara Cafe Ice', src: SERVER.assetBase + 'assets/packs/cafe-ice.webp' },
    { name: 'Zara Cardamom Milk Tea 200g', src: SERVER.assetBase + 'assets/packs/cardamom-pouch.webp' },
  ];
  const BASE_PACK = 4; // the stand-up pouch sits on the table at the start

  // Logo on the tea stall sign in the background garden.
  const LOGO = { src: SERVER.assetBase + 'assets/logo-badge.png', img: null };

  // All game logic runs in virtual units: the screen is always VW units wide.
  const VW = 400;
  const PACK_W = 116, ART_PAD = 12;
  const OVERLAP = 0.06;   // each pack settles slightly onto the crimped seal of the one below
  const MIN_X = PACK_W / 2 + 6, MAX_X = VW - PACK_W / 2 - 6;
  const PERFECT_TOL = 7;
  const DROP_GAP = 190;   // distance kept between the moving pack and the tower top
  const GRAVITY = 3000;
  const BASE_SPEED = 230, MAX_SPEED = 560, SPEED_STEP = 0.06;
  const SCENE_H = 330;
  // Zara tea stall on the lawn right of the tower: x, y = middle of its base in scene units, k = scale.
  // paintStall draws it in its own units with (0, 0) at that base point and negative y going up.
  const STALL = { x: 330, y: SCENE_H - 40, k: 0.8 };
  const STALL_CUPS = [6, 22, 38]; // cup positions on the counter; steam rises from these and the urn
  const STALL_URN = -30;
  const CONFETTI = ['#ffd23f', '#e4252b', '#ffffff', '#2c9a3c', '#1f57c8', '#7a3db0'];

  // Tower physics, switched on with a difficulty level 1–5 in Admin → Settings (names and descriptions
  // are Setting::PHYSICS_LEVELS on the server). Per level:
  //   sway    how far the top of a tall tower swings to each side (units)
  //   period  seconds per sway
  //   lean    how much of the tower's off-centre weight shows as lean at the top
  //   kick    how hard an off-centre landing jolts the tower
  //   grip    how far the weight above a pack may hang past it before it tips, as a share of half a
  //           pack (1 = right to the edge, which is also the rule when physics is off)
  const PHYSICS_LEVELS = [
    null,
    { sway: 8, period: 3.4, lean: 0.25, kick: 0.6, grip: 1 },
    { sway: 14, period: 3.1, lean: 0.4, kick: 0.8, grip: 0.9 },
    { sway: 22, period: 2.8, lean: 0.55, kick: 1, grip: 0.8 },
    { sway: 32, period: 2.5, lean: 0.75, kick: 1.25, grip: 0.7 },
    { sway: 44, period: 2.2, lean: 1, kick: 1.5, grip: 0.6 },
  ];

  // ───────────────────────── Helpers ─────────────────────────
  const $ = (id) => document.getElementById(id);
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));
  const rand = (a, b) => a + Math.random() * (b - a);
  const fmt = (n) => n.toLocaleString('en-US');
  const mmss = (s) => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
  const easeOutBack = (x) => { const c1 = 1.70158, c3 = c1 + 1; return 1 + c3 * (x - 1) ** 3 + c1 * (x - 1) ** 2; };
  const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (ch) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));

  function mulberry32(a) {
    return () => {
      a = (a + 0x6d2b79f5) | 0;
      let t = Math.imul(a ^ (a >>> 15), 1 | a);
      t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }

  function hexToRgb(h) { const n = parseInt(h.slice(1), 16); return [(n >> 16) & 255, (n >> 8) & 255, n & 255]; }
  function mixRgb(a, b, t) { return `rgb(${a.map((v, i) => Math.round(v + (b[i] - v) * t)).join(',')})`; }

  function rr(c, x, y, w, h, r) {
    c.beginPath();
    c.moveTo(x + r, y);
    c.arcTo(x + w, y, x + w, y + h, r);
    c.arcTo(x + w, y + h, x, y + h, r);
    c.arcTo(x, y + h, x, y, r);
    c.arcTo(x, y, x + w, y, r);
    c.closePath();
  }

  function makeCanvas(wu, hu, px) {
    const cv = document.createElement('canvas');
    cv.width = Math.ceil(wu * px);
    cv.height = Math.ceil(hu * px);
    const c = cv.getContext('2d');
    c.scale(px, px);
    return [cv, c];
  }

  const store = {
    get(k, d) { try { const v = localStorage.getItem('zaraTower.' + k); return v === null ? d : JSON.parse(v); } catch { return d; } },
    set(k, v) { try { localStorage.setItem('zaraTower.' + k, JSON.stringify(v)); } catch { /* storage unavailable */ } },
  };

  const vibrate = (p) => { try { navigator.vibrate && navigator.vibrate(p); } catch { /* unsupported */ } };

  // ───────────────────────── Sound ─────────────────────────
  const Sound = {
    ctx: null,
    on: store.get('sound', true),
    init() {
      if (!this.ctx) { const AC = window.AudioContext || window.webkitAudioContext; if (AC) this.ctx = new AC(); }
      if (this.ctx && this.ctx.state === 'suspended') this.ctx.resume();
    },
    tone(freq, dur, { type = 'sine', vol = 0.15, to = 0, delay = 0 } = {}) {
      if (!this.on || !this.ctx) return;
      const t = this.ctx.currentTime + delay;
      const o = this.ctx.createOscillator(), g = this.ctx.createGain();
      o.type = type;
      o.frequency.setValueAtTime(freq, t);
      if (to) o.frequency.exponentialRampToValueAtTime(to, t + dur);
      g.gain.setValueAtTime(0.0001, t);
      g.gain.exponentialRampToValueAtTime(vol, t + 0.012);
      g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
      o.connect(g);
      g.connect(this.ctx.destination);
      o.start(t);
      o.stop(t + dur + 0.02);
    },
    count() { this.tone(520, 0.14, { type: 'triangle', vol: 0.14 }); },
    go() { this.tone(784, 0.1, { type: 'triangle', vol: 0.16 }); this.tone(1046, 0.25, { type: 'triangle', vol: 0.16, delay: 0.09 }); },
    drop() { this.tone(600, 0.12, { type: 'triangle', vol: 0.06, to: 260 }); },
    land() { this.tone(170, 0.16, { vol: 0.35, to: 70 }); this.tone(420, 0.05, { type: 'square', vol: 0.03 }); },
    perfect(streak) {
      const k = 2 ** (Math.min(streak - 1, 7) / 12);
      [660, 880, 1320].forEach((f, i) => this.tone(f * k, 0.2, { type: 'triangle', vol: 0.13, delay: i * 0.06 }));
      this.land();
    },
    miss() { this.tone(330, 0.45, { type: 'sawtooth', vol: 0.07, to: 70 }); },
    collapse() { [0, 0.08, 0.16, 0.24].forEach((d, i) => this.tone(200 - i * 30, 0.18, { vol: 0.3, to: 50, delay: d })); },
    tick() { this.tone(1200, 0.05, { type: 'square', vol: 0.04 }); },
    pop() { this.tone(620, 0.12, { type: 'triangle', vol: 0.12, to: 1100 }); },
    over() { [784, 659, 523, 392].forEach((f, i) => this.tone(f, 0.22, { type: 'triangle', vol: 0.13, delay: i * 0.13 })); },
  };

  // ───────────────────────── DOM ─────────────────────────
  const el = {
    screens: { start: $('screen-start'), game: $('screen-game'), over: $('screen-over') },
    canvas: $('game'), hud: $('hud'),
    form: $('start-form'), nick: $('nickname'), phone: $('phone'), collage: $('collage'),
    nameField: $('name-field'), phoneField: $('phone-field'),
    time: $('time'), timeBox: $('time-box'), score: $('score'), scoreBox: $('score-box'),
    tapHint: $('tap-hint'), countdown: $('countdown'), banner: $('banner'),
    pauseOverlay: $('pause-overlay'), btnPause: $('btn-pause'), btnResume: $('btn-resume'),
    btnRestart: $('btn-restart'), btnSound: $('btn-sound'), btnQuit: $('btn-quit'),
    overScore: $('over-score'), overHeight: $('over-height'), overBest: $('over-best'),
    overPerfects: $('over-perfects'), newBest: $('new-best'),
    btnSubmit: $('btn-submit'), submitLabel: $('submit-label'), btnAgain: $('btn-again'), btnMenu: $('btn-menu'),
    lbModal: $('lb-modal'), lbList: $('lb-list'), lbClose: $('lb-close'), btnLbStart: $('btn-lb-start'),
    tip: $('tip'), bestChip: $('best-chip'), bestVal: $('best-val'),
    closedTitle: $('closed-title'), closedMessage: $('closed-message'),
  };
  const ctx = el.canvas.getContext('2d');

  // ───────────────────────── Artwork ─────────────────────────
  // pack height in units, from the photo's aspect ratio
  const packH = (f) => {
    const img = PACKS[f].img;
    return img ? (PACK_W * img.naturalHeight) / img.naturalWidth : PACK_W * 1.2;
  };

  function renderPack(f, px) {
    const h = packH(f);
    const [cv, c] = makeCanvas(PACK_W + ART_PAD * 2, h + ART_PAD * 2, px);
    c.translate(ART_PAD, ART_PAD);
    c.imageSmoothingQuality = 'high';
    c.shadowColor = 'rgba(50,20,0,0.35)'; c.shadowBlur = 8; c.shadowOffsetY = 4;
    if (PACKS[f].img) c.drawImage(PACKS[f].img, 0, 0, PACK_W, h);
    else { rr(c, 0, 0, PACK_W, h, 12); c.fillStyle = '#c3151d'; c.fill(); }
    return cv;
  }

  function tree(c, x, y, r) {
    c.fillStyle = '#6b4226';
    c.fillRect(x - r * 0.12, y - r * 0.9, r * 0.24, r * 0.95);
    const greens = ['#3e9a3a', '#4fb245', '#2f7f2e', '#5cc04f', '#45a640'];
    [[0, -1.5, 1], [-0.62, -1.15, 0.72], [0.62, -1.2, 0.75], [-0.25, -2.0, 0.7], [0.35, -1.95, 0.66]].forEach(([bx, by, br], i) => {
      c.fillStyle = greens[i]; c.beginPath(); c.arc(x + bx * r, y + by * r, br * r, 0, Math.PI * 2); c.fill();
    });
    c.fillStyle = 'rgba(255,255,255,0.13)';
    c.beginPath(); c.arc(x - 0.3 * r, y - 1.95 * r, 0.35 * r, 0, Math.PI * 2); c.fill();
  }

  function windowPane(c, x, y, w, h, arched) {
    c.fillStyle = '#fff6ea';
    if (arched) { c.beginPath(); c.moveTo(x - 2, y + h + 2); c.lineTo(x - 2, y + w / 2); c.arc(x + w / 2, y + w / 2, w / 2 + 2, Math.PI, 0); c.lineTo(x + w + 2, y + h + 2); c.closePath(); c.fill(); }
    else c.fillRect(x - 2, y - 2, w + 4, h + 4);
    const g = c.createLinearGradient(0, y, 0, y + h);
    g.addColorStop(0, '#b9e4f7'); g.addColorStop(1, '#5fa6d2');
    c.fillStyle = g;
    if (arched) { c.beginPath(); c.moveTo(x, y + h); c.lineTo(x, y + w / 2); c.arc(x + w / 2, y + w / 2, w / 2, Math.PI, 0); c.lineTo(x + w, y + h); c.closePath(); c.fill(); }
    else c.fillRect(x, y, w, h);
    c.fillStyle = '#fff6ea';
    c.fillRect(x + w / 2 - 0.75, y, 1.5, h);
    c.fillRect(x, y + h * 0.5, w, 1.5);
  }

  function paintScene(c) {
    const W = VW, H = SCENE_H, rnd = mulberry32(42);
    // distant hills
    c.fillStyle = '#bfe5d3';
    c.beginPath(); c.moveTo(0, H - 120);
    c.bezierCurveTo(80, H - 165, 150, H - 120, 220, H - 140);
    c.bezierCurveTo(300, H - 165, 350, H - 125, W, H - 148);
    c.lineTo(W, H); c.lineTo(0, H); c.closePath(); c.fill();

    // campus building
    const bx = 28, bw = W - 56, by = H - 200, bh = 150;
    const cx = 145, cw = 110, cy = by - 52;
    c.fillStyle = '#b4523a'; c.fillRect(bx - 6, by - 10, bw + 12, 12);
    let g = c.createLinearGradient(0, by, 0, by + bh);
    g.addColorStop(0, '#f2b68c'); g.addColorStop(1, '#de8f62');
    c.fillStyle = g; c.fillRect(bx, by, bw, bh);
    c.fillStyle = 'rgba(255,255,255,0.45)';
    c.fillRect(bx, by + 46, bw, 3); c.fillRect(bx, by + 94, bw, 3);
    for (let row = 0; row < 3; row++) {
      for (let x = bx + 12; x < bx + bw - 18; x += 27) {
        if (x > cx - 24 && x < cx + cw + 4) continue;
        windowPane(c, x, by + 12 + row * 48, 16, 26, false);
      }
    }
    // central block with pediment and clock
    c.fillStyle = '#c45d3e';
    c.beginPath(); c.moveTo(cx - 10, cy + 2); c.lineTo(cx + cw / 2, cy - 30); c.lineTo(cx + cw + 10, cy + 2); c.closePath(); c.fill();
    c.fillStyle = '#f7cfa8'; c.fillRect(cx, cy, cw, by + bh - cy);
    c.fillStyle = '#fff'; c.beginPath(); c.arc(cx + cw / 2, cy - 9, 9, 0, Math.PI * 2); c.fill();
    c.strokeStyle = '#8a4a22'; c.lineWidth = 2; c.stroke();
    c.beginPath(); c.moveTo(cx + cw / 2, cy - 9); c.lineTo(cx + cw / 2, cy - 15); c.moveTo(cx + cw / 2, cy - 9); c.lineTo(cx + cw / 2 + 4, cy - 7); c.stroke();
    for (let row = 0; row < 3; row++) {
      for (let k = 0; k < 3; k++) {
        if (row === 2 && k === 1) continue;
        windowPane(c, cx + 16 + k * 31, cy + 14 + row * 50, 16, 30, true);
      }
    }
    c.fillStyle = '#7a3a1c';
    c.beginPath(); c.moveTo(cx + cw / 2 - 15, by + bh); c.lineTo(cx + cw / 2 - 15, by + bh - 26);
    c.arc(cx + cw / 2, by + bh - 26, 15, Math.PI, 0); c.lineTo(cx + cw / 2 + 15, by + bh); c.closePath(); c.fill();
    c.fillStyle = '#efe0cc'; c.fillRect(cx - 6, by + bh - 4, cw + 12, 6);
    // atmospheric haze over the building only (not the transparent sky)
    c.save();
    c.globalCompositeOperation = 'source-atop';
    c.fillStyle = 'rgba(215,238,255,0.2)'; c.fillRect(0, 0, W, H - 50);
    c.restore();

    // trees, hedge and lawn
    for (let i = 0; i < 15; i++) tree(c, (i / 14) * W + (rnd() - 0.5) * 22, H - 50, 19 + rnd() * 15);
    tree(c, -12, H - 38, 56);
    tree(c, W + 12, H - 38, 52);
    c.fillStyle = '#3f8f2e'; rr(c, -10, H - 62, W + 20, 26, 13); c.fill();
    g = c.createLinearGradient(0, H - 46, 0, H);
    g.addColorStop(0, '#80d35c'); g.addColorStop(1, '#4b9e36');
    c.fillStyle = g; c.fillRect(0, H - 46, W, 46);

    paintStall(c);
  }

  const STALL_RED = '#d42a25', STALL_YELLOW = '#f6c453', BRAND_RED = '#a52422';

  function paintStall(c) {
    c.save();
    c.translate(STALL.x, STALL.y);
    c.scale(STALL.k, STALL.k);
    const s = c.getTransform().a; // shadow sizes are in device pixels, so scale them by hand

    c.fillStyle = 'rgba(30,70,20,0.3)';
    c.beginPath(); c.ellipse(0, 0, 68, 6, 0, 0, Math.PI * 2); c.fill();

    // inside: back wall and a shelf showing off the packs
    let g = c.createLinearGradient(0, -128, 0, -46);
    g.addColorStop(0, '#3d1a08'); g.addColorStop(1, '#6b3416');
    c.fillStyle = g; c.fillRect(-50, -128, 100, 82);
    c.fillStyle = '#c48444'; c.fillRect(-48, -88, 96, 3);
    c.fillStyle = '#8a4f22'; c.fillRect(-48, -85, 96, 1.5);
    [1, 0, 4, 2, 3].forEach((f, i) => {
      const img = PACKS[f].img;
      if (!img) return;
      const w = 14, h = (w * img.naturalHeight) / img.naturalWidth;
      c.drawImage(img, -36 + i * 18 - w / 2, -88 - h, w, h);
    });
    g = c.createLinearGradient(0, -118, 0, -98);
    g.addColorStop(0, 'rgba(20,6,0,0.55)'); g.addColorStop(1, 'rgba(20,6,0,0)');
    c.fillStyle = g; c.fillRect(-50, -118, 100, 20);

    for (const x of [-54, 50]) {
      c.fillStyle = '#fff4e2'; c.fillRect(x, -112, 4, 66);
      c.fillStyle = 'rgba(120,60,20,0.25)'; c.fillRect(x + 2.6, -112, 1.4, 66);
    }

    // counter: wooden top, red front with the tagline on a yellow band
    g = c.createLinearGradient(0, -47, 0, -40);
    g.addColorStop(0, '#e6ad6c'); g.addColorStop(1, '#c48444');
    c.fillStyle = g; rr(c, -58, -47, 116, 7, 2); c.fill();
    g = c.createLinearGradient(0, -40, 0, -4);
    g.addColorStop(0, STALL_RED); g.addColorStop(1, '#a3171c');
    c.fillStyle = g; c.fillRect(-54, -40, 108, 36);
    c.fillStyle = 'rgba(0,0,0,0.12)';
    for (let x = -36; x < 54; x += 18) { c.fillRect(x, -40, 1.2, 10); c.fillRect(x, -16, 1.2, 12); }
    c.fillStyle = STALL_YELLOW; c.fillRect(-54, -30, 108, 14);
    c.fillStyle = 'rgba(165,36,34,0.35)'; c.fillRect(-54, -30, 108, 1); c.fillRect(-54, -17, 108, 1);
    c.fillStyle = BRAND_RED;
    c.font = '10px "Lilita One", "Fredoka", sans-serif';
    c.textAlign = 'center'; c.textBaseline = 'middle';
    c.fillText('Cup for any vibe', 0, -22.5, 100);
    c.fillStyle = '#6a3510'; c.fillRect(-56, -4, 112, 4);

    paintUrn(c, STALL_URN, -46);
    STALL_CUPS.forEach((x, i) => paintCup(c, x, -46, i === 1));

    // striped awning with a scalloped edge and a string of lights
    for (let i = 0; i < 8; i++) {
      const bx = -64 + i * 16;
      c.fillStyle = i % 2 ? STALL_YELLOW : STALL_RED;
      c.beginPath();
      c.moveTo(-56 + i * 14, -134); c.lineTo(-56 + (i + 1) * 14, -134);
      c.lineTo(bx + 16, -118); c.arc(bx + 8, -118, 8, 0, Math.PI);
      c.closePath(); c.fill();
    }
    c.fillStyle = 'rgba(255,255,255,0.2)'; c.fillRect(-56, -134, 112, 3);
    for (let i = 0; i <= 8; i++) {
      const x = -64 + i * 16;
      c.fillStyle = 'rgba(255,225,120,0.4)'; c.beginPath(); c.arc(x, -116, 3.2, 0, Math.PI * 2); c.fill();
      c.fillStyle = '#fff3b0'; c.beginPath(); c.arc(x, -116, 1.6, 0, Math.PI * 2); c.fill();
    }

    // roof with the Zara sign standing on it
    c.fillStyle = '#7a3a1c'; rr(c, -62, -139, 124, 6, 2); c.fill();
    c.fillStyle = 'rgba(255,255,255,0.25)'; c.fillRect(-60, -139, 120, 1.2);
    c.fillStyle = '#5a2c12'; c.fillRect(-26, -149, 3, 10); c.fillRect(23, -149, 3, 10);
    c.shadowColor = 'rgba(60,20,0,0.35)'; c.shadowBlur = 3 * s; c.shadowOffsetY = 1.5 * s;
    if (LOGO.img) {
      const w = 76, h = (w * LOGO.img.naturalHeight) / LOGO.img.naturalWidth;
      c.drawImage(LOGO.img, -w / 2, -143 - h, w, h);
    } else {
      rr(c, -38, -186, 76, 43, 12);
      c.fillStyle = '#f0b542'; c.fill();
      c.shadowColor = 'transparent';
      c.lineWidth = 3; c.strokeStyle = BRAND_RED; c.stroke();
      c.fillStyle = BRAND_RED; c.font = '26px "Lilita One", "Fredoka", sans-serif';
      c.fillText('Zara', 0, -164);
    }
    c.restore();
  }

  function paintUrn(c, x, base) {
    c.fillStyle = '#4d565e'; rr(c, x - 11, base - 4, 22, 4, 1.5); c.fill();
    const g = c.createLinearGradient(x - 12, 0, x + 12, 0);
    g.addColorStop(0, '#7d8993'); g.addColorStop(0.35, '#f3f6f8'); g.addColorStop(0.7, '#b9c3cb'); g.addColorStop(1, '#6b7680');
    c.fillStyle = g; rr(c, x - 12, base - 28, 24, 25, 6); c.fill();
    c.beginPath(); c.ellipse(x, base - 28, 9, 4, 0, Math.PI, 0); c.fill();
    c.fillStyle = STALL_RED; c.fillRect(x - 12, base - 19, 24, 6);
    c.fillStyle = STALL_YELLOW; c.fillRect(x - 12, base - 16.7, 24, 1.4);
    c.fillStyle = '#3d1a08'; c.beginPath(); c.arc(x, base - 33, 1.8, 0, Math.PI * 2); c.fill();
    c.fillStyle = '#d9a441'; rr(c, x + 10, base - 11, 7, 3, 1.5); c.fill();
    c.fillRect(x + 15, base - 9, 2, 3);
  }

  // a red Zara cup of coffee, or a glass of milk tea
  function paintCup(c, x, base, glass) {
    c.fillStyle = '#f6f1ea';
    c.beginPath(); c.ellipse(x, base - 1, 8, 2, 0, 0, Math.PI * 2); c.fill();
    c.beginPath(); c.moveTo(x - 6, base - 11); c.lineTo(x + 6, base - 11); c.lineTo(x + 4, base - 2); c.lineTo(x - 4, base - 2); c.closePath();
    if (glass) {
      c.fillStyle = '#c88a52'; c.fill();
      c.strokeStyle = 'rgba(255,255,255,0.9)'; c.lineWidth = 1; c.stroke();
      c.fillStyle = '#f3dcbc';
    } else {
      c.fillStyle = STALL_RED; c.fill();
      c.strokeStyle = STALL_RED; c.lineWidth = 1.6;
      c.beginPath(); c.arc(x + 5.4, base - 7, 2.6, -Math.PI / 2, Math.PI / 2); c.stroke();
      c.fillStyle = STALL_YELLOW; c.fillRect(x - 5.3, base - 8, 10.6, 1.4);
      c.fillStyle = '#5e2f14';
    }
    c.beginPath(); c.ellipse(x, base - 11, 6, 1.5, 0, 0, Math.PI * 2); c.fill();
  }

  function paintCloud(c) {
    c.fillStyle = 'rgba(170,210,240,0.45)';
    c.beginPath(); c.ellipse(80, 60, 62, 9, 0, 0, Math.PI * 2); c.fill();
    c.fillStyle = '#fff';
    [[42, 44, 22], [72, 32, 29], [104, 40, 25], [128, 50, 16], [24, 52, 14]].forEach(([x, y, r]) => {
      c.beginPath(); c.arc(x, y, r, 0, Math.PI * 2); c.fill();
    });
    rr(c, 14, 42, 128, 22, 11); c.fill();
  }

  const art = { px: 0, packs: [], h: [], scene: null, cloud: null };
  function buildArt(px) {
    art.px = px;
    art.packs = PACKS.map((_, i) => renderPack(i, px));
    art.h = PACKS.map((_, i) => packH(i));
    let c;
    [art.scene, c] = makeCanvas(VW, SCENE_H, px); paintScene(c);
    [art.cloud, c] = makeCanvas(160, 72, px); paintCloud(c);
  }

  function buildCollage() {
    // [pack, left %, top %, rotation]: three in the back row, two in front
    const layout = [[1, 0, 3, -9], [2, 33, 0, 2], [0, 66, 4, 9], [3, 16, 41, -5], [4, 50, 43, 6]];
    el.collage.textContent = '';
    layout.forEach(([fi, l, t, r], i) => {
      // the wrapper flies in and floats; the image inside wiggles when tapped
      const pk = document.createElement('span');
      pk.className = 'pk';
      pk.style.left = l + '%';
      pk.style.top = t + '%';
      pk.style.setProperty('--r', r + 'deg');
      pk.style.setProperty('--i', i);
      const img = new Image();
      img.src = PACKS[fi].src;
      img.alt = PACKS[fi].name;
      img.draggable = false;
      pk.appendChild(img);
      el.collage.appendChild(pk);
    });
  }

  // split the title into letters so they can bounce in and ripple
  function splitTitle() {
    let i = 0;
    document.querySelectorAll('.title .line').forEach((line) => {
      const text = line.textContent;
      line.textContent = '';
      for (const ch of text) {
        const s = document.createElement('span');
        s.className = 'ch';
        s.textContent = ch;
        s.style.setProperty('--i', i++);
        line.appendChild(s);
      }
    });
  }

  function updateStartMeta() {
    const best = store.get('best', 0);
    el.bestChip.hidden = !best;
    el.bestVal.textContent = fmt(best);
  }

  const TIPS = [
    'Tap anywhere to drop the pack',
    'Perfect stack = +200 points & +1 second',
    'Missed drops cost 3 seconds',
    'Keep it straight or the tower topples!',
  ];
  let tipIndex = 0;
  function nextTip() {
    if (!el.screens.start.classList.contains('active')) return;
    const tips = physics.enabled ? [...TIPS, 'The tower sways, so time your drop!'] : TIPS;
    tipIndex = (tipIndex + 1) % tips.length;
    el.tip.classList.remove('enter', 'swap');
    void el.tip.offsetWidth;
    el.tip.textContent = tips[tipIndex];
    el.tip.classList.add('swap');
  }

  // ───────────────────────── View ─────────────────────────
  const view = { scale: 1, dpr: 1, h: 800, ground: 650, hoverBottom: 280 };

  function resize() {
    const r = el.screens.game.getBoundingClientRect();
    if (r.width < 10 || r.height < 10) return;
    view.dpr = Math.min(window.devicePixelRatio || 1, 2.5);
    view.scale = r.width / VW;
    view.h = r.height / view.scale;
    el.canvas.width = Math.round(r.width * view.dpr);
    el.canvas.height = Math.round(r.height * view.dpr);
    const hudBottom = (el.hud.getBoundingClientRect().bottom - r.top) / view.scale;
    view.ground = view.h - Math.max(120, view.h * 0.16);
    view.hoverBottom = hudBottom + 16 + Math.max(...PACKS.map((_, i) => packH(i)));
    const px = view.scale * view.dpr;
    if (Math.abs(px - art.px) > 0.01) buildArt(px);
  }

  // world y is measured upwards from the table top; the camera scrolls up as the tower grows
  const toScreen = (wy) => view.ground - wy + S.camY;
  const toWorld = (sy) => view.ground + S.camY - sy;

  // ───────────────────────── State ─────────────────────────
  const S = {
    mode: 'menu', prevMode: 'play', round: 0,
    time: 0, score: 0, shownScore: 0, landed: 0, perfects: 0, streak: 0,
    tower: [], mover: null, debris: [], parts: [], texts: [], flashes: [],
    camY: 0, shake: 0, spawnIn: 0, dir: 1, lastFlavor: -1, lastSecond: 0,
    cdStep: 0, cdTimer: 0, firstDrop: false, submitted: false, overTimer: 0,
    // tower physics for this round (a PHYSICS_LEVELS row, or null for the normal game), the tower's
    // sway (see swayX) and sections of tower that are tipping over
    phys: null, sway: null, toppling: [],
    // server round: resolve to the play id (or null if the server couldn't be reached)
    playReq: Promise.resolve(null), finishReq: Promise.resolve(null),
  };
  let clouds = [];
  let roundNo = 0; // S.round: tells a late server reply which round it belongs to

  const speed = () => Math.min(MAX_SPEED, BASE_SPEED * (1 + SPEED_STEP * S.landed));
  const newPack = (x, f, y) => ({ x, f, y, h: packH(f), rot: 0, rv: 0, sq: 0 });
  // world y where the next pack lands
  const topY = () => { const t = S.tower[S.tower.length - 1]; return t.y + t.h * (1 - OVERLAP); };

  function initClouds() {
    const rnd = mulberry32(7);
    clouds = [];
    for (let i = 0; i < 28; i++) {
      clouds.push({ x: rnd() * (VW + 200) - 100, y: view.h * 0.5 - rnd() * 3400, s: 0.55 + rnd() * 0.8, v: 4 + rnd() * 10, a: 0.75 + rnd() * 0.25 });
    }
  }

  function spawnMover() {
    let f;
    do { f = (Math.random() * PACKS.length) | 0; } while (f === S.lastFlavor);
    S.lastFlavor = f;
    S.dir = -S.dir;
    S.mover = { x: S.dir > 0 ? MIN_X : MAX_X, dir: S.dir, f, state: 'move', wy: 0, vy: 0, t: 0 };
  }

  // ───────────────────────── Game flow ─────────────────────────
  function showScreen(name) {
    for (const [k, s] of Object.entries(el.screens)) s.classList.toggle('active', k === name);
  }

  function startGame() {
    // Play Again / Restart while the admin has playing disabled: the start screen shows their notice
    if (!gameOpen) { toMenu(); return; }
    Sound.init();
    clearTimeout(S.overTimer);
    const round = ++roundNo;
    Object.assign(S, {
      round,
      phys: physics.enabled ? PHYSICS_LEVELS[clamp(Math.round(physics.difficulty) || 3, 1, 5)] : null,
      sway: { d: 0, H: 0, phase: 0, lean: 0, kx: 0, kv: 0, risk: 0 },
      toppling: [],
      time: CONFIG.GAME_TIME, score: 0, shownScore: 0, landed: 0, perfects: 0, streak: 0,
      tower: [newPack(VW / 2, BASE_PACK, 0)], mover: null, debris: [], parts: [], texts: [], flashes: [],
      camY: 0, shake: 0, spawnIn: 0, dir: 1, lastFlavor: BASE_PACK, lastSecond: CONFIG.GAME_TIME,
      firstDrop: false, submitted: false,
    });
    S.sway.H = topY();
    // every round (Play, Play Again, Restart) is its own play on the server; the game never waits for it
    S.playReq = api('start', { name: store.get('nick', ''), phone: store.get('phone', '') })
      .then((r) => r.play_id)
      .catch((err) => {
        // the admin disabled playing since this page last checked: end the round and show their notice
        if (err.data && err.data.closed) {
          applyStatus(err.data);
          if (S.round === round && S.mode !== 'over' && S.mode !== 'menu') toMenu();
        }
        return null;
      });
    S.finishReq = Promise.resolve(null);
    el.tapHint.classList.remove('hide');
    el.banner.classList.remove('show');
    el.pauseOverlay.classList.remove('show');
    showScreen('game');
    resize();
    initClouds();
    spawnMover();
    updateHud(true);
    S.mode = 'countdown';
    S.cdStep = 3;
    S.cdTimer = 0;
    showCount('3');
    Sound.count();
  }

  function showCount(txt) {
    el.countdown.textContent = '';
    const s = document.createElement('span');
    s.textContent = txt;
    if (txt === 'GO!') s.className = 'go';
    el.countdown.appendChild(s);
  }

  function drop() {
    const m = S.mover;
    if (S.mode !== 'play' || !m || m.state !== 'move') return;
    m.state = 'fall';
    m.vy = 120;
    m.wy = toWorld(view.hoverBottom);
    Sound.drop();
    if (!S.firstDrop) { S.firstDrop = true; el.tapHint.classList.add('hide'); }
  }

  function land(m) {
    const top = S.tower[S.tower.length - 1];
    // with physics on the tower sways, so the pack lands on the top wherever it is right now
    const ty = topY(), sx = swayX(ty);
    const dx = m.x - (top.x + sx), adx = Math.abs(dx), half = PACK_W / 2;
    S.mover = null;
    S.spawnIn = 0.3;
    if (adx > half) { fallOff(m, dx); return; }
    if (S.phys && adx > half * S.phys.grip) { tipOff(m, dx); return; }

    const perfect = adx <= PERFECT_TOL;
    const p = newPack(perfect ? top.x : m.x - sx, m.f, ty);
    p.sq = 0.14;
    if (!perfect) p.rv = (dx / half) * 1.1;
    S.tower.push(p);
    S.landed++;
    const pTop = p.y + p.h;
    dust(p.x + sx, p.y);
    if (S.phys) {
      // an off-centre landing jolts the tower (more so when it's tall); a perfect one steadies it
      const w = S.sway;
      if (perfect) { w.kv *= 0.4; w.kx *= 0.6; } else w.kv += S.phys.kick * (dx / half) * 130 * clamp(w.H / 800, 0.3, 1);
    }

    let pts;
    if (perfect) {
      S.streak++;
      S.perfects++;
      pts = 100 + CONFIG.PERFECT_BONUS;
      S.time += CONFIG.PERFECT_TIME_BONUS;
      timePop('+' + CONFIG.PERFECT_TIME_BONUS + 's', true);
      perfectFx(p);
      Sound.perfect(S.streak);
      vibrate(30);
      S.shake = 6;
    } else {
      S.streak = 0;
      const q = 1 - adx / half;
      pts = Math.round(50 + 50 * q);
      const lines = [{ text: '+' + pts, size: 28, fill: '#fff', stroke: '#4a220c' }];
      if (q > 0.75) lines.unshift({ text: 'GREAT!', size: 22, fill: '#ffd23f', stroke: '#4a220c' });
      else if (q < 0.25) lines.unshift({ text: 'CLOSE ONE!', size: 20, fill: '#fff', stroke: '#4a220c' });
      S.texts.push({ lines, x: clamp(p.x + sx, 60, VW - 60), wy: pTop + 24, rot: 0, age: 0, life: 0.8 });
      Sound.land();
      S.shake = 3;
    }
    S.score += pts;
    bump(el.scoreBox);
    // with physics on the balance is checked every frame (see update)
    if (!S.phys) checkStability();
  }

  function fallOff(m, dx) {
    const dir = Math.sign(dx) || 1;
    const touching = Math.abs(dx) < PACK_W;
    S.debris.push({ x: m.x, wy: m.wy, f: m.f, vx: dir * (touching ? 90 : 20), vy: touching ? m.vy * 0.3 : m.vy, rot: 0, vr: dir * (touching ? 4.5 : 0.6), delay: 0 });
    missed(m.x);
  }

  // physics on: the pack landed hanging too far over the edge, so it tips off it
  function tipOff(m, dx) {
    const ty = topY();
    S.tower.push(newPack(m.x - swayX(ty), m.f, ty));
    topple(S.tower.length - 1, Math.sign(dx), false);
    missed(m.x);
  }

  function missed(x) {
    S.streak = 0;
    S.time = Math.max(0, S.time - CONFIG.MISS_PENALTY);
    timePop('-' + CONFIG.MISS_PENALTY + 's', false);
    S.texts.push({
      lines: [{ text: 'MISS!', size: 36, fill: '#e4252b', stroke: '#fff' }],
      x: clamp(x, 70, VW - 70), wy: topY() + 60, rot: 0.08, age: 0, life: 0.9,
    });
    Sound.miss();
    vibrate([40, 30, 40]);
    S.shake = 4;
  }

  // ───────────────────────── Tower physics ─────────────────────────
  // With physics on the tower bends like a flexible column: the point at height y sits swayX(y) to the
  // side of where it was stacked and is tilted by swayRot(y). The base stays put and the top, at the
  // (smoothed) tower height H, moves by d. Both are 0 when physics is off.
  const swayX = (y) => (S.phys && S.sway.H > 0 ? S.sway.d * (y / S.sway.H) ** 2 : 0);
  const swayRot = (y) => (S.phys && S.sway.H > 0 ? (2 * S.sway.d * y) / (S.sway.H * S.sway.H) : 0);

  function updateSway(dt) {
    const L = S.phys, w = S.sway, t = S.tower;
    w.H += (topY() - w.H) * (1 - Math.exp(-dt * 4));
    // a smooth sway that builds up as the tower gets taller
    w.phase += (dt * 2 * Math.PI) / L.period;
    const amp = L.sway * clamp((w.H - 250) / 1100, 0, 1);
    // lean towards the side the weight is on
    let off = 0;
    if (t.length > 1) {
      let sum = 0;
      for (let j = 1; j < t.length; j++) sum += t[j].x;
      off = sum / (t.length - 1) - t[0].x;
    }
    w.lean += (off * L.lean - w.lean) * (1 - Math.exp(-dt * 3));
    // jolts from landings ring out on a damped spring
    w.kv += (-36 * w.kx - 4 * w.kv) * dt;
    w.kx += w.kv * dt;
    // a fast tremble warns that the tower is close to tipping
    const tremble = Math.sin(w.phase * 17) * 2.5 * clamp((w.risk - 0.75) / 0.25, 0, 1);
    w.d = amp * Math.sin(w.phase) + w.lean + w.kx + tremble;
  }

  // Physics on: every frame, does the weight above any pack hang past the edge it may lean over, where
  // the packs are right now (swaying, leaning)? If so the tower tips over that pack.
  function checkBalance() {
    const t = S.tower, edge = (PACK_W / 2) * S.phys.grip;
    let risk = 0;
    for (let i = 0; i < t.length - 1; i++) {
      const support = t[i].x + swayX(t[i].y + t[i].h * (1 - OVERLAP));
      let sum = 0;
      for (let j = i + 1; j < t.length; j++) sum += t[j].x + swayX(t[j].y + t[j].h / 2);
      const off = sum / (t.length - i - 1) - support;
      if (Math.abs(off) > edge) { topple(i + 1, Math.sign(off), true); return; }
      risk = Math.max(risk, Math.abs(off) / edge);
    }
    S.sway.risk = risk;
  }

  // Physics on: the packs from `from` up pivot on the top edge of the pack below them, tipping over
  // faster and faster (a tall section more slowly than a single pack), then fall away as debris.
  function topple(from, dir, announce) {
    const t = S.tower, below = t[from - 1];
    const py = below.y + below.h * (1 - OVERLAP);
    const px = below.x + swayX(py) + (dir * PACK_W) / 2;
    const packs = t.splice(from).map((p) => ({ f: p.f, x: p.x + swayX(p.y), y: p.y, rot: p.rot + swayRot(p.y) }));
    const last = packs[packs.length - 1];
    const height = Math.max(60, last.y + art.h[last.f] - py);
    S.toppling.push({ px, py, dir, height, th: 0, w: dir * 0.3, packs });
    S.sway.kv -= dir * 40; // the rest of the tower recoils
    S.sway.risk = 0;
    if (!announce) return;
    S.streak = 0;
    S.texts.push({
      lines: [{ text: 'OOPS!', size: 40, fill: '#e4252b', stroke: '#fff' }, { text: 'TOWER TOPPLED', size: 22, fill: '#fff', stroke: '#4a220c' }],
      x: VW / 2, wy: topY() + 90, rot: -0.06, age: 0, life: 1.3,
    });
    Sound.collapse();
    vibrate(120);
    S.shake = 8;
  }

  // where a toppling pack is: its stacked position turned by th about the pivot (clockwise for th > 0)
  function toppledPose(tp, p) {
    const rx = p.x - tp.px, ry = p.y - tp.py, cos = Math.cos(tp.th), sin = Math.sin(tp.th);
    return { x: rx * cos + ry * sin, y: -rx * sin + ry * cos };
  }

  function updateToppling(dt) {
    for (const tp of S.toppling) {
      // gravity's pull grows as the section leans further past the pivot
      tp.w += tp.dir * ((1.5 * GRAVITY) / tp.height) * Math.sin(Math.abs(tp.th) + 0.12) * dt;
      tp.th += tp.w * dt;
      if (Math.abs(tp.th) < 1) continue;
      // let go: each pack flies off with the speed it had while turning
      for (const p of tp.packs) {
        const r = toppledPose(tp, p);
        S.debris.push({ x: tp.px + r.x, wy: tp.py + r.y, f: p.f, vx: tp.w * r.y + tp.dir * 20, vy: tp.w * r.x, rot: p.rot + tp.th, vr: tp.w, delay: 0 });
      }
      tp.done = true;
    }
    S.toppling = S.toppling.filter((tp) => !tp.done);
  }

  // Physics off: a pack topples when the centre of mass of everything above it hangs past its edge.
  function checkStability() {
    const t = S.tower;
    for (let i = 0; i < t.length - 2; i++) {
      let sum = 0;
      for (let j = i + 1; j < t.length; j++) sum += t[j].x;
      const off = sum / (t.length - i - 1) - t[i].x;
      if (Math.abs(off) > PACK_W / 2) { collapse(i + 1, Math.sign(off)); return; }
    }
  }

  function collapse(from, dir) {
    const fallen = S.tower.splice(from);
    fallen.forEach((p, k) => {
      S.debris.push({ x: p.x, wy: p.y, f: p.f, vx: dir * (50 + k * 35), vy: -40 - k * 15, rot: p.rot, vr: dir * (1.5 + k * 0.6), delay: k * 0.04 });
    });
    S.streak = 0;
    S.texts.push({
      lines: [{ text: 'OOPS!', size: 40, fill: '#e4252b', stroke: '#fff' }, { text: 'TOWER TOPPLED', size: 22, fill: '#fff', stroke: '#4a220c' }],
      x: VW / 2, wy: topY() + 90, rot: -0.06, age: 0, life: 1.3,
    });
    Sound.collapse();
    vibrate(120);
    S.shake = 12;
  }

  function endGame() {
    S.mode = 'over';
    if (S.mover) {
      dust(S.mover.x, S.mover.state === 'fall' ? S.mover.wy : toWorld(view.hoverBottom));
      S.mover = null;
    }
    el.banner.classList.add('show');
    Sound.over();
    const result = { score: S.score, height: S.tower.length - 1, perfects: S.perfects };
    S.finishReq = S.playReq
      .then((id) => id && api('finish', { play_id: id, ...result }).then(() => id))
      .catch(() => null);
    S.overTimer = setTimeout(showResults, 1500);
  }

  function showResults() {
    const height = S.tower.length - 1;
    const best = store.get('best', 0);
    const isBest = S.score > best;
    if (isBest) store.set('best', S.score);
    el.overHeight.textContent = height + (height === 1 ? ' Pack' : ' Packs');
    el.overBest.textContent = fmt(Math.max(best, S.score));
    el.overPerfects.textContent = S.perfects;
    el.newBest.hidden = !isBest;
    el.submitLabel.innerHTML = 'Submit to<br>Leaderboard';
    el.overScore.textContent = '0';
    showScreen('over');
    countUp(el.overScore, S.score, 900);
  }

  function pause() {
    if (S.mode !== 'play' && S.mode !== 'countdown') return;
    S.prevMode = S.mode;
    S.mode = 'paused';
    el.pauseOverlay.classList.add('show');
  }

  function resume() {
    if (S.mode !== 'paused') return;
    S.mode = S.prevMode;
    el.pauseOverlay.classList.remove('show');
  }

  function toMenu() {
    clearTimeout(S.overTimer);
    S.mode = 'menu';
    el.pauseOverlay.classList.remove('show');
    el.banner.classList.remove('show');
    updateStartMeta();
    for (const node of enterEls) node.classList.add('enter');
    showScreen('start');
    refreshStatus();
  }

  // ───────────────────────── Server ─────────────────────────
  // GET when there's no body, otherwise POST JSON; rejects on network errors and non-2xx replies
  // (with the reply's status and JSON body on the error)
  async function api(name, body) {
    const res = await fetch(SERVER.routes[name], body === undefined
      ? { headers: { Accept: 'application/json' } }
      : { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    if (!res.ok) {
      const err = new Error(`${name} failed: ${res.status}`);
      err.status = res.status;
      err.data = await res.json().catch(() => null);
      throw err;
    }
    return res.json();
  }

  // Admin → Settings, via the page and the status poll: the admin can disable playing (the start screen
  // then shows their notice in place of the form) and switch on tower physics for the next round.
  // status: { open, title, message, physics: { enabled, difficulty } }
  let gameOpen = true;
  let physics = { enabled: false, difficulty: 3 };
  function applyStatus(status) {
    if (status.physics) physics = status.physics;
    gameOpen = !!status.open;
    el.screens.start.classList.toggle('is-closed', !gameOpen);
    el.closedTitle.textContent = status.title || '';
    el.closedMessage.textContent = status.message || '';
    el.closedMessage.hidden = !status.message;
  }

  // keeps the last known state if the server can't be reached
  function refreshStatus() {
    api('status').then(applyStatus).catch(() => {});
  }

  // ───────────────────────── Leaderboard ─────────────────────────
  async function submitScore() {
    if (S.submitted) { el.lbModal.classList.add('show'); return; }
    el.btnSubmit.disabled = true;
    el.submitLabel.textContent = 'Submitting…';
    showLeaderboard('<li class="lb-empty">Submitting your score…</li>');
    try {
      const id = await S.finishReq;
      if (!id) throw new Error('round was not recorded');
      renderStandings(await api('submit', { play_id: id }));
      S.submitted = true;
      el.submitLabel.innerHTML = 'View<br>Leaderboard';
    } catch {
      showLeaderboard('<li class="lb-empty">Couldn\'t submit your score.<br>Check your connection and try again.</li>');
      el.submitLabel.innerHTML = 'Try Again';
    } finally {
      el.btnSubmit.disabled = false;
    }
  }

  async function openLeaderboard() {
    showLeaderboard('<li class="lb-empty">Loading…</li>');
    try {
      renderStandings(await api('leaderboard'));
    } catch {
      showLeaderboard('<li class="lb-empty">Couldn\'t load the leaderboard.<br>Check your connection and try again.</li>');
    }
  }

  function showLeaderboard(html) {
    el.lbList.innerHTML = html;
    el.lbModal.classList.add('show');
  }

  function lbRow(e) {
    const cls = ['lb-row', e.rank <= 3 ? 'r' + e.rank : '', e.me ? 'me' : ''].join(' ');
    const h = e.height + (e.height === 1 ? ' pack' : ' packs');
    return `<li class="${cls}"><span class="lb-rank">${e.rank}</span><span class="lb-name">${escapeHtml(e.name)}<span class="lb-sub">${h}</span></span><span class="lb-score">${fmt(e.score)}</span></li>`;
  }

  // standings from the server: { leaderboard: [...rows], you?: row outside the top }
  function renderStandings(data) {
    const rows = data.leaderboard.map(lbRow);
    if (data.you) rows.push('<li class="lb-gap">• • •</li>' + lbRow(data.you));
    showLeaderboard(rows.length ? rows.join('') : '<li class="lb-empty">No scores yet.<br>Be the first to stack!</li>');
    const mine = el.lbList.querySelector('.me');
    if (mine) mine.scrollIntoView({ block: 'nearest' });
  }

  // ───────────────────────── Effects ─────────────────────────
  function dust(x, wy) {
    for (let i = 0; i < 8; i++) {
      const s = i % 2 ? 1 : -1;
      S.parts.push({ type: 'dust', x: x + s * rand(PACK_W * 0.25, PACK_W * 0.5), wy: wy + 2, vx: s * rand(30, 90), vy: rand(10, 50), g: 60, rot: 0, vr: 0, size: rand(3, 6), age: 0, life: rand(0.35, 0.55) });
    }
  }

  function perfectFx(p) {
    const top = p.y + p.h, x = p.x + swayX(p.y);
    S.flashes.push({ x: p.x, f: p.f, wy: p.y, age: 0, life: 0.5 }); // follows the sway as it's drawn
    for (let i = 0; i < 16; i++) {
      S.parts.push({ type: 'star', x: x + rand(-PACK_W / 2, PACK_W / 2), wy: top, vx: rand(-170, 170), vy: rand(140, 340), g: 700, rot: rand(0, 6), vr: rand(-8, 8), size: rand(5, 10), age: 0, life: rand(0.7, 1.1) });
    }
    for (let i = 0; i < 14; i++) {
      S.parts.push({ type: 'confetti', color: CONFETTI[i % CONFETTI.length], x: x + rand(-40, 40), wy: top + 10, vx: rand(-200, 200), vy: rand(150, 380), g: 600, rot: rand(0, 6), vr: rand(-12, 12), size: rand(7, 11), age: 0, life: rand(0.8, 1.2) });
    }
    const lines = [
      { text: 'PERFECT', size: 34, fill: 'gold', stroke: '#4a220c' },
      { text: 'STACK!', size: 34, fill: 'gold', stroke: '#4a220c' },
      { text: '+' + CONFIG.PERFECT_BONUS, size: 38, fill: '#e4252b', stroke: '#fff' },
    ];
    if (S.streak > 1) lines.push({ text: `x${S.streak} COMBO`, size: 20, fill: '#fff', stroke: '#4a220c' });
    // replace any perfect banner still on screen so quick combos don't pile up
    S.texts = S.texts.filter((t) => !t.perfect);
    S.texts.push({ lines, x: clamp(x + 64, 80, VW - 80), wy: top + 60, rot: -0.14, age: 0, life: 1.25, perfect: true });
  }

  function timePop(txt, good) {
    const s = document.createElement('span');
    s.className = 'time-pop' + (good ? ' good' : '');
    s.textContent = txt;
    el.timeBox.appendChild(s);
    setTimeout(() => s.remove(), 900);
  }

  function bump(node) {
    node.classList.remove('bump');
    void node.offsetWidth;
    node.classList.add('bump');
  }

  function countUp(node, to, ms) {
    const t0 = performance.now();
    const step = (now) => {
      const p = Math.min(1, (now - t0) / ms);
      node.textContent = fmt(Math.round(to * (1 - (1 - p) ** 3)));
      if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  }

  // ───────────────────────── Update ─────────────────────────
  function update(dt) {
    if (S.mode === 'countdown') {
      S.cdTimer += dt;
      if (S.cdTimer >= 0.62) {
        S.cdTimer = 0;
        S.cdStep--;
        if (S.cdStep > 0) { showCount(String(S.cdStep)); Sound.count(); }
        else { showCount('GO!'); Sound.go(); S.mode = 'play'; }
      }
    }

    if (S.mode === 'play') {
      S.time -= dt;
      const sec = Math.ceil(S.time);
      if (sec !== S.lastSecond) { S.lastSecond = sec; if (sec > 0 && sec <= 5) Sound.tick(); }
      if (S.time <= 0) { S.time = 0; endGame(); }
    }

    const m = S.mover;
    if (m) {
      m.t += dt;
      if (m.state === 'move') {
        if (S.mode === 'play') {
          m.x += m.dir * speed() * dt;
          if (m.x > MAX_X) { m.x = 2 * MAX_X - m.x; m.dir = -1; }
          else if (m.x < MIN_X) { m.x = 2 * MIN_X - m.x; m.dir = 1; }
        }
      } else {
        m.vy += GRAVITY * dt;
        m.wy -= m.vy * dt;
        const ty = topY();
        if (m.wy <= ty) { m.wy = ty; land(m); }
      }
    } else if (S.mode === 'play') {
      S.spawnIn -= dt;
      if (S.spawnIn <= 0) spawnMover();
    }

    // landing wobble (spring) and squash
    for (const p of S.tower) {
      if (p.rv || p.rot) {
        p.rv += (-110 * p.rot - 10 * p.rv) * dt;
        p.rot += p.rv * dt;
        if (Math.abs(p.rot) < 0.0008 && Math.abs(p.rv) < 0.01) p.rot = p.rv = 0;
      }
      if (p.sq) { p.sq *= Math.exp(-dt * 12); if (p.sq < 0.002) p.sq = 0; }
    }

    if (S.phys) {
      updateSway(dt);
      if (S.mode === 'play') checkBalance();
    }
    updateToppling(dt);

    // keep the tower top DROP_GAP below the moving pack
    const target = Math.max(0, view.hoverBottom + DROP_GAP - view.ground + topY());
    S.camY += (target - S.camY) * (1 - Math.exp(-dt * 5));

    for (const d of S.debris) {
      if (d.delay > 0) { d.delay -= dt; continue; }
      d.vy += GRAVITY * dt;
      d.wy -= d.vy * dt;
      d.x += d.vx * dt;
      d.rot += d.vr * dt;
    }
    S.debris = S.debris.filter((d) => toScreen(d.wy) - art.h[d.f] * 1.5 < view.h);

    for (const p of S.parts) { p.age += dt; p.x += p.vx * dt; p.wy += p.vy * dt; p.vy -= p.g * dt; p.rot += p.vr * dt; }
    S.parts = S.parts.filter((p) => p.age < p.life);
    for (const t of S.texts) { t.age += dt; t.wy += 46 * dt; }
    S.texts = S.texts.filter((t) => t.age < t.life);
    for (const f of S.flashes) f.age += dt;
    S.flashes = S.flashes.filter((f) => f.age < f.life);

    S.shake *= Math.exp(-dt * 9);
    for (const cl of clouds) { cl.x += cl.v * dt; if (cl.x > VW + 40) cl.x = -160 * cl.s - 40; }
  }

  const hudCache = { sec: -1, score: -1 };
  function updateHud(force) {
    const sec = Math.max(0, Math.ceil(S.time));
    if (force || sec !== hudCache.sec) {
      hudCache.sec = sec;
      el.time.textContent = mmss(sec);
      el.timeBox.classList.toggle('low', sec <= 5);
    }
    if (force) S.shownScore = S.score;
    else if (S.shownScore < S.score) S.shownScore = Math.min(S.score, S.shownScore + Math.max(3, Math.ceil((S.score - S.shownScore) * 0.2)));
    if (force || S.shownScore !== hudCache.score) {
      hudCache.score = S.shownScore;
      el.score.textContent = fmt(S.shownScore);
    }
  }

  // ───────────────────────── Render ─────────────────────────
  const SKY = { top0: hexToRgb('#3f9fe6'), top1: hexToRgb('#1d4fa6'), bot0: hexToRgb('#d4f0ff'), bot1: hexToRgb('#83c6f4') };

  function drawPackAt(c, f, x, sb, rot, sx, sy, alpha) {
    c.save();
    c.translate(x, sb);
    if (rot) c.rotate(rot);
    if (sx !== 1 || sy !== 1) c.scale(sx, sy);
    if (alpha !== undefined) c.globalAlpha = alpha;
    const h = art.h[f];
    c.drawImage(art.packs[f], -PACK_W / 2 - ART_PAD, -h - ART_PAD, PACK_W + ART_PAD * 2, h + ART_PAD * 2);
    c.restore();
  }

  function drawSky(c) {
    const k = clamp(S.camY / 4000, 0, 1);
    const g = c.createLinearGradient(0, 0, 0, view.h);
    g.addColorStop(0, mixRgb(SKY.top0, SKY.top1, k));
    g.addColorStop(1, mixRgb(SKY.bot0, SKY.bot1, k));
    c.fillStyle = g;
    c.fillRect(-20, -20, VW + 40, view.h + 40);
  }

  function drawClouds(c) {
    for (const cl of clouds) {
      const sy = cl.y + S.camY * 0.3;
      if (sy < -80 || sy > view.h) continue;
      c.globalAlpha = cl.a;
      c.drawImage(art.cloud, cl.x, sy, 160 * cl.s, 72 * cl.s);
    }
    c.globalAlpha = 1;
  }

  function drawScene(c) {
    const bottom = view.ground + 24 + S.camY * 0.5;
    if (bottom - SCENE_H > view.h) return;
    c.drawImage(art.scene, 0, bottom - SCENE_H, VW, SCENE_H);
    drawStallSteam(c, bottom - SCENE_H);
    if (bottom < view.h + 20) { c.fillStyle = '#4b9e36'; c.fillRect(-20, bottom - 1, VW + 40, view.h - bottom + 40); }
  }

  // the stall itself is baked into the scene; only its steam moves
  function drawStallSteam(c, sceneTop) {
    const t = performance.now() / 1000, k = STALL.k;
    const vents = [[STALL_URN, -80, 0.8], ...STALL_CUPS.map((x) => [x, -58, 1])];
    vents.forEach(([lx, ly, size], v) => {
      const x0 = STALL.x + lx * k, y0 = sceneTop + STALL.y + ly * k;
      for (let j = 0; j < 3; j++) {
        const p = (t * 0.35 + j / 3 + v * 0.37) % 1;
        const x = x0 + Math.sin(p * 6 + v) * 2.5 * k, y = y0 - p * 20 * k, r = (2.5 + p * 4.5) * size * k;
        const g = c.createRadialGradient(x, y, 0, x, y, r);
        g.addColorStop(0, `rgba(255,255,255,${(0.42 * Math.sin(p * Math.PI)).toFixed(3)})`);
        g.addColorStop(1, 'rgba(255,255,255,0)');
        c.fillStyle = g;
        c.beginPath(); c.arc(x, y, r, 0, Math.PI * 2); c.fill();
      }
    });
  }

  function drawTable(c) {
    const y = toScreen(0);
    if (y - 20 > view.h + 20) return;
    let g = c.createLinearGradient(0, y - 14, 0, y + 8);
    g.addColorStop(0, '#e6ad6c'); g.addColorStop(1, '#c48444');
    c.fillStyle = g; c.fillRect(-20, y - 14, VW + 40, 22);
    c.fillStyle = 'rgba(255,255,255,0.35)'; c.fillRect(-20, y - 14, VW + 40, 2);
    g = c.createLinearGradient(0, y + 8, 0, y + 150);
    g.addColorStop(0, '#9c5a26'); g.addColorStop(1, '#6a3510');
    c.fillStyle = g; c.fillRect(-20, y + 8, VW + 40, Math.max(0, view.h - y + 30));
    c.fillStyle = 'rgba(0,0,0,0.25)'; c.fillRect(-20, y + 8, VW + 40, 4);
    c.strokeStyle = 'rgba(60,25,5,0.25)'; c.lineWidth = 1.2;
    for (let i = 0; i < 5; i++) {
      const ly = y + 32 + i * 32;
      if (ly > view.h + 10) break;
      c.beginPath(); c.moveTo(-20, ly); c.bezierCurveTo(120, ly - 6, 260, ly + 6, VW + 20, ly - 2); c.stroke();
    }
    const base = S.tower[0];
    if (base) {
      c.fillStyle = 'rgba(60,25,5,0.3)';
      c.beginPath(); c.ellipse(base.x, y + 2, PACK_W * 0.55, 7, 0, 0, Math.PI * 2); c.fill();
    }
  }

  function drawTower(c) {
    for (const p of S.tower) {
      const sb = toScreen(p.y);
      if (sb < -20 || sb - p.h > view.h + 20) continue;
      drawPackAt(c, p.f, p.x + swayX(p.y), sb, p.rot + swayRot(p.y), 1 + p.sq * 0.5, 1 - p.sq);
    }
    for (const tp of S.toppling) {
      for (const p of tp.packs) {
        const r = toppledPose(tp, p);
        drawPackAt(c, p.f, tp.px + r.x, toScreen(tp.py + r.y), p.rot + tp.th, 1, 1);
      }
    }
    for (const d of S.debris) drawPackAt(c, d.f, d.x, toScreen(d.wy), d.rot, 1, 1);
  }

  function drawMover(c) {
    const m = S.mover;
    if (!m) return;
    if (m.state !== 'move') { drawPackAt(c, m.f, m.x, toScreen(m.wy), 0, 0.97, 1.04); return; }

    const sb = view.hoverBottom;
    const topS = toScreen(topY());
    if (topS > sb) {
      const g = c.createLinearGradient(0, sb, 0, topS);
      g.addColorStop(0, 'rgba(255,255,255,0.28)'); g.addColorStop(1, 'rgba(255,255,255,0)');
      c.fillStyle = g;
      c.fillRect(m.x - PACK_W / 2 + 8, sb, PACK_W - 16, topS - sb);
    }
    c.save();
    c.setLineDash([9, 7]);
    c.strokeStyle = 'rgba(255,255,255,0.9)'; c.lineWidth = 2.5;
    c.beginPath(); c.moveTo(6, sb + 12); c.lineTo(VW - 6, sb + 12); c.stroke();
    c.restore();

    if (S.mode === 'play') {
      const trail = speed() * 0.016;
      for (let k = 3; k >= 1; k--) drawPackAt(c, m.f, m.x - m.dir * trail * k, sb, 0, 1, 1, 0.06 * (4 - k));
    }
    const sc = 0.5 + 0.5 * easeOutBack(Math.min(1, m.t / 0.22));
    drawPackAt(c, m.f, m.x, sb, 0, sc, sc);
  }

  function drawFlashes(c) {
    for (const fl of S.flashes) {
      const p = fl.age / fl.life, sb = toScreen(fl.wy), h = art.h[fl.f], pad = 3 + p * 22, x = fl.x + swayX(fl.wy);
      c.save();
      c.globalAlpha = 1 - p;
      rr(c, x - PACK_W / 2 - pad, sb - h - pad, PACK_W + pad * 2, h + pad * 2, 14 + pad * 0.5);
      c.lineWidth = 5 * (1 - p) + 1; c.strokeStyle = '#ffe36b'; c.stroke();
      c.restore();
      if (p < 0.5) {
        // brighten the pack itself for a quick glow
        c.save();
        c.globalCompositeOperation = 'lighter';
        drawPackAt(c, fl.f, x, sb, swayRot(fl.wy), 1, 1, 0.55 * (1 - p * 2));
        c.restore();
      }
    }
  }

  function starPath(c, r) {
    c.beginPath();
    for (let i = 0; i < 10; i++) {
      const a = -Math.PI / 2 + (i * Math.PI) / 5, rr2 = i % 2 ? r * 0.45 : r;
      c.lineTo(Math.cos(a) * rr2, Math.sin(a) * rr2);
    }
    c.closePath();
  }

  function drawParticles(c) {
    for (const p of S.parts) {
      const t = p.age / p.life;
      c.save();
      c.translate(p.x, toScreen(p.wy));
      c.rotate(p.rot);
      c.globalAlpha = 1 - t * t;
      if (p.type === 'dust') {
        c.globalAlpha *= 0.7;
        c.fillStyle = '#fff';
        c.beginPath(); c.arc(0, 0, p.size * (1 + t), 0, Math.PI * 2); c.fill();
      } else if (p.type === 'star') {
        starPath(c, p.size);
        c.fillStyle = '#ffd84a'; c.fill();
        c.lineWidth = 1.2; c.strokeStyle = '#c47d0a'; c.stroke();
      } else {
        c.fillStyle = p.color;
        c.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
      }
      c.restore();
    }
  }

  function drawTexts(c) {
    for (const t of S.texts) {
      const p = t.age / t.life;
      const sc = p < 0.15 ? Math.max(0.01, easeOutBack(p / 0.15)) : 1;
      c.save();
      c.translate(t.x, toScreen(t.wy));
      c.rotate(t.rot);
      c.scale(sc, sc);
      c.globalAlpha = p > 0.7 ? 1 - (p - 0.7) / 0.3 : 1;
      c.textAlign = 'center'; c.textBaseline = 'middle'; c.lineJoin = 'round';
      const total = t.lines.reduce((s, l) => s + l.size * 0.95, 0);
      let y = -total / 2;
      for (const l of t.lines) {
        y += l.size * 0.475;
        c.font = `${l.size}px "Lilita One", "Fredoka", sans-serif`;
        c.lineWidth = l.size * 0.24;
        c.strokeStyle = l.stroke;
        c.strokeText(l.text, 0, y);
        if (l.fill === 'gold') {
          const g = c.createLinearGradient(0, y - l.size / 2, 0, y + l.size / 2);
          g.addColorStop(0, '#fff3a0'); g.addColorStop(0.5, '#ffd23f'); g.addColorStop(1, '#f39a12');
          c.fillStyle = g;
        } else c.fillStyle = l.fill;
        c.fillText(l.text, 0, y);
        y += l.size * 0.475;
      }
      c.restore();
    }
  }

  function render() {
    if (!art.packs.length) return;
    const c = ctx, s = view.scale * view.dpr;
    c.setTransform(s, 0, 0, s, 0, 0);
    drawSky(c);
    if (S.shake > 0.3) c.translate((Math.random() - 0.5) * S.shake, (Math.random() - 0.5) * S.shake);
    drawClouds(c);
    drawScene(c);
    drawTable(c);
    drawTower(c);
    drawMover(c);
    drawFlashes(c);
    drawParticles(c);
    drawTexts(c);
  }

  // ───────────────────────── Loop ─────────────────────────
  let last = performance.now();
  function frame(now) {
    const dt = Math.min(0.05, (now - last) / 1000);
    last = now;
    if (el.screens.game.classList.contains('active')) {
      if (S.mode !== 'paused' && S.mode !== 'menu') update(dt);
      render();
      updateHud(false);
    }
    requestAnimationFrame(frame);
  }

  // ───────────────────────── Input ─────────────────────────
  // shows the error pill and shakes the field, or clears it when msg is empty
  function setFieldError(field, msg) {
    field.classList.remove('invalid', 'shake', 'enter');
    if (!msg) return;
    field.querySelector('.field-msg').textContent = msg;
    void field.offsetWidth;
    field.classList.add('invalid', 'shake');
  }

  el.form.addEventListener('submit', (e) => {
    e.preventDefault();
    if (!gameOpen) return;
    const name = el.nick.value.trim();
    const phone = el.phone.value.replace(/[\s()-]/g, '');
    const nameErr = name ? '' : 'Enter your name';
    const phoneErr = CONFIG.PHONE_PATTERN.test(phone) ? '' : phone ? 'Check your number' : 'Enter your number';
    setFieldError(el.nameField, nameErr);
    setFieldError(el.phoneField, phoneErr);
    if (nameErr || phoneErr) { (nameErr ? el.nick : el.phone).focus(); return; }
    store.set('nick', name);
    store.set('phone', phone);
    if (document.activeElement) document.activeElement.blur();
    Sound.init(); // unlock audio inside the tap, before waiting on the images
    artReady.then(startGame);
  });

  // Enter on the name field moves to the phone field instead of submitting
  el.nick.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') { e.preventDefault(); el.phone.focus(); }
  });
  // keep the phone field to digits, +, spaces and dashes
  el.phone.addEventListener('input', () => {
    const clean = el.phone.value.replace(/[^\d+\s-]/g, '');
    if (clean !== el.phone.value) el.phone.value = clean;
  });
  for (const field of [el.nameField, el.phoneField]) {
    field.querySelector('input').addEventListener('input', () => field.classList.remove('invalid', 'shake', 'enter'));
  }

  // The welcome-screen entrance runs once per visit. Dropping the class afterwards stops later
  // class changes (error shake, tip swap) from replaying it and hiding the element again.
  const enterEls = [...document.querySelectorAll('#screen-start .enter')];
  for (const node of enterEls) {
    node.addEventListener('animationend', (e) => { if (e.animationName === 'riseIn') node.classList.remove('enter'); });
  }

  el.screens.game.addEventListener('pointerdown', (e) => {
    if (e.target.closest('button, .overlay')) return;
    e.preventDefault();
    drop();
  });

  document.addEventListener('keydown', (e) => {
    if (!el.screens.game.classList.contains('active')) return;
    if (e.code === 'Space' || e.code === 'Enter' || e.code === 'ArrowDown') {
      e.preventDefault();
      if (S.mode === 'paused') resume(); else drop();
    } else if (e.code === 'Escape' || e.code === 'KeyP') {
      if (S.mode === 'paused') resume(); else pause();
    }
  });

  el.btnPause.addEventListener('click', pause);
  el.btnResume.addEventListener('click', resume);
  el.btnRestart.addEventListener('click', startGame);
  el.btnQuit.addEventListener('click', toMenu);
  el.btnSound.addEventListener('click', () => {
    Sound.on = !Sound.on;
    store.set('sound', Sound.on);
    el.btnSound.textContent = 'Sound: ' + (Sound.on ? 'On' : 'Off');
  });

  el.btnSubmit.addEventListener('click', submitScore);
  el.btnAgain.addEventListener('click', startGame);
  el.btnMenu.addEventListener('click', toMenu);
  el.btnLbStart.addEventListener('click', openLeaderboard);
  el.collage.addEventListener('pointerdown', (e) => {
    const img = e.target.closest('.pk img');
    if (!img) return;
    Sound.init();
    Sound.pop();
    img.classList.remove('wiggle');
    void img.offsetWidth;
    img.classList.add('wiggle');
  });
  el.lbClose.addEventListener('click', () => el.lbModal.classList.remove('show'));
  el.lbModal.addEventListener('click', (e) => { if (e.target === el.lbModal) el.lbModal.classList.remove('show'); });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) pause();
    else if (el.screens.start.classList.contains('active')) refreshStatus();
  });
  const landscape = window.matchMedia('(orientation: landscape) and (max-height: 500px) and (pointer: coarse)');
  if (landscape.addEventListener) landscape.addEventListener('change', (e) => { if (e.matches) pause(); });

  if (window.ResizeObserver) new ResizeObserver(resize).observe(el.screens.game);
  window.addEventListener('resize', resize);

  // ───────────────────────── Boot ─────────────────────────
  el.nick.value = store.get('nick', '');
  el.phone.value = store.get('phone', '');
  el.btnSound.textContent = 'Sound: ' + (Sound.on ? 'On' : 'Off');

  // pack heights come from the photos and the background stall shows the logo, so the game waits for
  // them before the first round
  const loadImage = (item) => new Promise((resolve) => {
    const img = new Image();
    img.onload = () => { item.img = img; resolve(); };
    img.onerror = () => { console.error('Could not load image', item.src); resolve(); };
    img.src = item.src;
  });
  const artReady = Promise.all([...PACKS.map(loadImage), loadImage(LOGO)]).then(() => { art.px = 0; resize(); });
  // the stall's tagline is baked into the scene canvas, so repaint it once the web fonts are in
  if (document.fonts) document.fonts.ready.then(() => { art.px = 0; resize(); });

  buildCollage();
  splitTitle();
  updateStartMeta();
  applyStatus(SERVER.status);
  setInterval(nextTip, 3200);
  // a start screen left open (an event kiosk) follows the admin opening or closing the game
  setInterval(() => {
    if (el.screens.start.classList.contains('active') && !document.hidden) refreshStatus();
  }, 30000);

  requestAnimationFrame(frame);
})();
