<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <meta name="theme-color" content="#3f1c08">
  <title>Leaderboard · ZARA Tower Challenge</title>
  <link rel="icon" type="image/png" href="{{ asset('game/assets/logo-badge.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Lilita+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('game/leaderboard.css') }}?v={{ filemtime(public_path('game/leaderboard.css')) }}">
</head>
<body>
  {{-- A 1080×1920 portrait stage, scaled to fit the window by leaderboard.js --}}
  <div class="stage" id="stage">

    <div class="bg" aria-hidden="true">
      <div class="sunburst"></div>
      <div class="glow"></div>
      <div class="sparkles">
        <i style="--x:8%;--y:27%;--d:0s;--s:1.2"></i>
        <i class="w" style="--x:90%;--y:29%;--d:.8s;--s:1.4"></i>
        <i style="--x:5%;--y:44%;--d:1.6s;--s:1"></i>
        <i class="w" style="--x:93%;--y:47%;--d:.4s;--s:1.1"></i>
        <i style="--x:30%;--y:24%;--d:2.2s;--s:.9"></i>
        <i class="w" style="--x:70%;--y:23%;--d:1.2s;--s:1"></i>
        <i style="--x:50%;--y:50%;--d:2.8s;--s:.8"></i>
      </div>
      <div class="fall">
        <i class="bean" style="--x:4%;--t:17s;--dl:-2s;--sc:1.3;--rot:260deg;--dx:40px"></i>
        <i class="leaf" style="--x:16%;--t:21s;--dl:-11s;--sc:1.2;--rot:-200deg;--dx:-50px"></i>
        <i class="choc" style="--x:36%;--t:24s;--dl:-16s;--sc:.9;--rot:220deg;--dx:-30px"></i>
        <i class="pod" style="--x:55%;--t:19s;--dl:-5s;--sc:1.2;--rot:-160deg;--dx:30px"></i>
        <i class="bean" style="--x:68%;--t:20s;--dl:-8s;--sc:1;--rot:-240deg;--dx:45px"></i>
        <i class="leaf" style="--x:84%;--t:22s;--dl:-18s;--sc:1.3;--rot:200deg;--dx:-40px"></i>
        <i class="bean" style="--x:95%;--t:18s;--dl:-13s;--sc:1.1;--rot:300deg;--dx:-35px"></i>
      </div>
    </div>

    <div class="drip" aria-hidden="true">
      <svg viewBox="0 0 1080 110" preserveAspectRatio="none">
        <defs><linearGradient id="chocTop" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7a3a17"/><stop offset="1" stop-color="#4a220c"/></linearGradient></defs>
        <g fill="url(#chocTop)">
          <path d="M0 0H1080V30Q1053 40 1026 32T972 32T918 32T864 32T810 32T756 32T702 32T648 32T594 32T540 32T486 32T432 32T378 32T324 32T270 32T216 32T162 32T108 32T54 32T0 32Z"/>
          <path class="d" style="--t:3.2s;--dl:-.4s" d="M36 24v50a12 12 0 0 0 24 0v-50z"/>
          <path class="d" style="--t:2.6s;--dl:-1.2s" d="M128 24v30a10 10 0 0 0 20 0v-30z"/>
          <path class="d" style="--t:3.6s;--dl:-2s" d="M222 24v62a14 14 0 0 0 28 0v-62z"/>
          <path class="d" style="--t:2.8s;--dl:-.8s" d="M330 24v22a10 10 0 0 0 20 0v-22z"/>
          <path class="d" style="--t:3.4s;--dl:-1.6s" d="M420 24v40a12 12 0 0 0 24 0v-40z"/>
          <path class="d" style="--t:2.9s;--dl:-.3s" d="M528 24v26a11 11 0 0 0 22 0v-26z"/>
          <path class="d" style="--t:3.8s;--dl:-2.4s" d="M624 24v56a14 14 0 0 0 28 0v-56z"/>
          <path class="d" style="--t:3s;--dl:-1s" d="M726 24v30a10 10 0 0 0 20 0v-30z"/>
          <path class="d" style="--t:3.3s;--dl:-1.8s" d="M818 24v48a12 12 0 0 0 24 0v-48z"/>
          <path class="d" style="--t:2.7s;--dl:-.6s" d="M920 24v24a10 10 0 0 0 20 0v-24z"/>
          <path class="d" style="--t:3.5s;--dl:-2.2s" d="M1006 24v44a12 12 0 0 0 24 0v-44z"/>
        </g>
        <path d="M0 9H1080" stroke="rgba(255,255,255,.14)" stroke-width="3"/>
      </svg>
    </div>

    <div class="live" id="live"><i></i><span id="live-text">LIVE</span></div>

    <header class="head">
      <div class="stat stat--l"><span>Players</span><b id="stat-players">0</b></div>
      <img class="logo" src="{{ asset('game/assets/logo.png') }}" alt="Zara — Cup for any vibe">
      <div class="stat stat--r"><span>Games today</span><b id="stat-games">0</b></div>
      <h1 class="title" aria-label="Tower Challenge"><span class="t-white">Tower</span> <span class="t-gold">Challenge</span></h1>
      <div class="ribbon"><span>Leaderboard</span></div>
    </header>

    <section class="podium" id="podium" aria-label="Top three">
      @foreach ([2, 1, 3] as $rank)
        <div class="spot spot--{{ $rank }} empty" data-rank="{{ $rank }}">
          <div class="who">
            @if ($rank === 1)
              <svg class="crown" viewBox="0 0 120 84" aria-hidden="true"><path d="M8 26l26 22 26-40 26 40 26-22-10 48H18z" fill="#ffcf2e" stroke="#b9770a" stroke-width="5" stroke-linejoin="round"/><rect x="16" y="70" width="88" height="12" rx="5" fill="#f0a91c" stroke="#b9770a" stroke-width="4"/><circle cx="60" cy="8" r="7" fill="#e4252b" stroke="#b9770a" stroke-width="3"/><circle cx="8" cy="24" r="6" fill="#e4252b" stroke="#b9770a" stroke-width="3"/><circle cx="112" cy="24" r="6" fill="#e4252b" stroke="#b9770a" stroke-width="3"/></svg>
            @endif
            <div class="avatar"><span class="face">?</span></div>
            <div class="name">Your name?</div>
            <div class="score">0</div>
            <div class="packs">&nbsp;</div>
          </div>
          <div class="block"><span class="num">{{ $rank }}</span></div>
        </div>
      @endforeach
    </section>

    <section class="list" id="list" aria-label="Places 4 to 10"></section>

    <section class="ticker" aria-label="Just played">
      <span class="ticker-label"><i></i>Just played</span>
      <div class="ticker-track"><div class="ticker-run" id="ticker"></div></div>
    </section>

    <footer class="foot">
      <svg class="foot-wave foot-wave--back" viewBox="0 0 2160 60" preserveAspectRatio="none" aria-hidden="true"><path d="M0 30C135 0 405 60 540 30C675 0 945 60 1080 30C1215 0 1485 60 1620 30C1755 0 2025 60 2160 30V60H0Z" fill="#8f4d22"/></svg>
      <svg class="foot-wave foot-wave--front" viewBox="0 0 2160 60" preserveAspectRatio="none" aria-hidden="true"><path d="M0 34C135 12 405 56 540 34C675 12 945 56 1080 34C1215 12 1485 56 1620 34C1755 12 2025 56 2160 34V60H0Z" fill="#4a220c"/></svg>
      <div class="foot-body">
        <div class="qr-card" id="qr-card"><div class="qr" id="qr"></div></div>
        <div class="cta">
          <div class="cta-title" id="cta-title">Scan to play!</div>
          <div class="cta-sub" id="cta-sub">Stack the packs. Top the board.</div>
          <div class="cta-url" id="cta-url"></div>
        </div>
        <div class="foot-packs" aria-hidden="true">
          <img src="{{ asset('game/assets/packs/cardamom-pouch.webp') }}" alt="">
          <img src="{{ asset('game/assets/packs/cafe.webp') }}" alt="">
        </div>
      </div>
    </footer>

    {{-- shown when a round finishes --}}
    <div class="toast" id="toast" aria-live="polite">
      <div class="toast-head" id="toast-head">Just played!</div>
      <div class="toast-body">
        <div class="avatar"><span class="face" id="toast-face">?</span></div>
        <div class="toast-text">
          <div class="toast-name" id="toast-name"></div>
          <div class="toast-score"><b id="toast-score">0</b><span>points</span></div>
          <div class="toast-packs" id="toast-packs"></div>
        </div>
      </div>
    </div>

    {{-- shown when someone takes first place --}}
    <div class="champion" id="champion" aria-live="polite">
      <div class="champion-ribbon">New champion!</div>
      <div class="champion-name" id="champion-name"></div>
    </div>

    <canvas class="confetti" id="confetti" width="1080" height="1920" aria-hidden="true"></canvas>
  </div>

  <script>window.KIOSK = @json($kiosk);</script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode-generator/1.4.4/qrcode.min.js" integrity="sha512-ZDSPMa/JM1D+7kdg2x3BsruQ6T/JpJo3jWDWkCZsP+5yVyp1KfESqLI+7RqB5k24F7p2cV7i2YHh/890y6P6Sw==" crossorigin="anonymous" referrerpolicy="no-referrer" defer></script>
  <script src="{{ asset('game/leaderboard.js') }}?v={{ filemtime(public_path('game/leaderboard.js')) }}" defer></script>
</body>
</html>
