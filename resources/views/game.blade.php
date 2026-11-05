<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
  <meta name="theme-color" content="#fcecd6">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <title>ZARA Tower Challenge</title>
  <link rel="icon" type="image/png" href="{{ asset('game/assets/logo-badge.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Lilita+One&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('game/styles.css') }}?v={{ filemtime(public_path('game/styles.css')) }}">
</head>
<body>
  <div id="app">

    <!-- ============ 1. START ============ -->
    <section id="screen-start" @class(['screen', 'screen--start', 'active', 'is-closed' => ! $status['open']]) aria-label="Start">
      <div class="start-bg" aria-hidden="true">
        <div class="sunburst"></div>
        <div class="hero-glow"></div>

        <!-- steaming drinks either side of the logo -->
        <div class="cup cup--coffee" style="--dd:0s">
          <svg class="cup-art" viewBox="0 0 120 110">
            <defs>
              <linearGradient id="cupRed" x1="0" x2="1"><stop offset="0" stop-color="#9d1418"/><stop offset=".38" stop-color="#ea3b30"/><stop offset=".62" stop-color="#d42a25"/><stop offset="1" stop-color="#8e1115"/></linearGradient>
              <radialGradient id="coffeeTop" cx=".45" cy=".4" r=".7"><stop offset="0" stop-color="#9a5b2e"/><stop offset=".6" stop-color="#5e2f14"/><stop offset="1" stop-color="#3a1a08"/></radialGradient>
            </defs>
            <ellipse cx="60" cy="101" rx="54" ry="8" fill="rgba(80,30,10,.18)"/>
            <ellipse cx="60" cy="95" rx="56" ry="12" fill="#f6f1ea"/>
            <ellipse cx="60" cy="93" rx="40" ry="7.5" fill="#e3dacd"/>
            <g transform="translate(16 93) rotate(-25)"><ellipse rx="6" ry="4.2" fill="#5b2e12"/><path d="M-5 0q5-2 10 0" fill="none" stroke="#2b1205" stroke-width="1.2"/></g>
            <g transform="translate(104 96) rotate(20)"><ellipse rx="6" ry="4.2" fill="#5b2e12"/><path d="M-5 0q5-2 10 0" fill="none" stroke="#2b1205" stroke-width="1.2"/></g>
            <path d="M93 46c19-3 23 25 1 31" fill="none" stroke="#b81a1e" stroke-width="8" stroke-linecap="round"/>
            <path d="M21 36h78c0 33-12 56-39 56S21 69 21 36z" fill="url(#cupRed)"/>
            <path d="M22.5 57Q60 65 97.5 57" fill="none" stroke="#f4c24f" stroke-width="4"/>
            <path d="M28 44c1 18 6 31 15 38" fill="none" stroke="rgba(255,255,255,.35)" stroke-width="4" stroke-linecap="round"/>
            <ellipse cx="60" cy="36" rx="39" ry="10" fill="#fbe6e2"/>
            <ellipse cx="60" cy="37.5" rx="34.5" ry="8" fill="url(#coffeeTop)"/>
          </svg>
          <span class="latte"><i></i></span>
          <i class="steam" style="left:30%;animation-delay:0s"></i>
          <i class="steam" style="left:44%;animation-delay:1.1s"></i>
          <i class="steam" style="left:58%;animation-delay:2.2s"></i>
          <span class="drop-lane"><i></i></span>
          <i class="ripple"></i>
        </div>

        <div class="cup cup--tea" style="--dd:2.1s">
          <svg class="cup-art" viewBox="0 0 120 110">
            <defs>
              <linearGradient id="teaFill" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f3dcbc"/><stop offset=".22" stop-color="#e2b483"/><stop offset=".6" stop-color="#c88a52"/><stop offset="1" stop-color="#9c5d2b"/></linearGradient>
              <clipPath id="glassClip"><path d="M27 30h66l-5 58q-1 6-7 6H39q-6 0-7-6z"/></clipPath>
            </defs>
            <ellipse cx="60" cy="101" rx="54" ry="8" fill="rgba(80,30,10,.18)"/>
            <ellipse cx="60" cy="95" rx="56" ry="12" fill="#f6f1ea"/>
            <ellipse cx="60" cy="93" rx="40" ry="7.5" fill="#e3dacd"/>
            <g transform="translate(15 94) rotate(-20)"><ellipse rx="7" ry="4" fill="#8cc152"/><path d="M-5 0h10" stroke="#4f7f1f" stroke-width="1"/></g>
            <g transform="translate(105 95) rotate(25)"><ellipse rx="7" ry="4" fill="#7bb342"/><path d="M-5 0h10" stroke="#4f7f1f" stroke-width="1"/></g>
            <path d="M28 44c-20-2-22 28-1 30" fill="none" stroke="rgba(255,255,255,.95)" stroke-width="7" stroke-linecap="round"/>
            <g clip-path="url(#glassClip)">
              <rect x="20" y="30" width="80" height="70" fill="url(#teaFill)"/>
              <path d="M28 58c14 6 24-6 36 0s18 4 28-2" fill="none" stroke="rgba(255,248,235,.55)" stroke-width="4"/>
            </g>
            <path d="M25 26h70l-6 63q-1 7-8 7H39q-7 0-8-7z" fill="rgba(255,255,255,.16)" stroke="rgba(255,255,255,.95)" stroke-width="2.5"/>
            <path d="M33 36l3 50" stroke="rgba(255,255,255,.7)" stroke-width="4" stroke-linecap="round"/>
            <ellipse cx="60" cy="31" rx="33" ry="7.5" fill="#f7e6cc"/>
            <ellipse cx="60" cy="26.5" rx="35" ry="7" fill="none" stroke="rgba(255,255,255,.95)" stroke-width="2.5"/>
          </svg>
          <i class="bubble" style="left:36%;top:24%;animation-delay:.2s"></i>
          <i class="bubble" style="left:52%;top:27%;animation-delay:1.1s"></i>
          <i class="bubble" style="left:63%;top:23%;animation-delay:1.8s"></i>
          <i class="steam" style="left:30%;animation-delay:0s"></i>
          <i class="steam" style="left:44%;animation-delay:1.1s"></i>
          <i class="steam" style="left:58%;animation-delay:2.2s"></i>
          <span class="drop-lane"><i></i></span>
          <i class="ripple"></i>
        </div>

        <div class="sparkles">
          <i style="--x:12%;--y:22%;--d:0s;--s:.9"></i>
          <i class="w" style="--x:84%;--y:24%;--d:.7s;--s:1"></i>
          <i style="--x:6%;--y:36%;--d:1.4s;--s:.7"></i>
          <i class="w" style="--x:90%;--y:40%;--d:.3s;--s:.8"></i>
          <i style="--x:18%;--y:53%;--d:1.9s;--s:.8"></i>
          <i class="w" style="--x:80%;--y:57%;--d:1.1s;--s:.9"></i>
          <i class="w" style="--x:30%;--y:26%;--d:2.6s;--s:.6"></i>
          <i style="--x:70%;--y:30%;--d:1.6s;--s:.7"></i>
        </div>
        <div class="fall">
          <i class="bean" style="--x:5%;--t:11s;--dl:-1s;--sc:.9;--rot:260deg;--dx:30px"></i>
          <i class="leaf" style="--x:20%;--t:14s;--dl:-7s;--sc:.8;--rot:-200deg;--dx:-40px"></i>
          <i class="choc" style="--x:40%;--t:16s;--dl:-10s;--sc:.6;--rot:220deg;--dx:-20px"></i>
          <i class="bean" style="--x:58%;--t:13s;--dl:-2.5s;--sc:.7;--rot:-240deg;--dx:35px"></i>
          <i class="leaf" style="--x:72%;--t:15s;--dl:-12s;--sc:1;--rot:200deg;--dx:-30px"></i>
          <i class="pod" style="--x:84%;--t:11.5s;--dl:-6s;--sc:.85;--rot:-160deg;--dx:20px"></i>
          <i class="bean" style="--x:93%;--t:14.5s;--dl:-9s;--sc:.75;--rot:300deg;--dx:-25px"></i>
        </div>

        <!-- melting chocolate along the top; two drips feed the cups -->
        <div class="choc-drip">
          <svg viewBox="0 0 400 64" preserveAspectRatio="none">
            <defs><linearGradient id="chocTop" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#7a3a17"/><stop offset="1" stop-color="#4a220c"/></linearGradient></defs>
            <g fill="url(#chocTop)">
              <path d="M0 0H400V11Q380 16 360 12T320 12T280 12T240 12T200 12T160 12T120 12T80 12T40 12T0 12Z"/>
              <path class="d" style="--t:3.2s;--dl:-.4s" d="M13 9v22a5 5 0 0 0 10 0v-22z"/>
              <path class="d d--feed" style="--dd:0s" d="M48 9v18a6 6 0 0 0 12 0v-18z"/>
              <path class="d" style="--t:2.6s;--dl:-1.2s" d="M87 9v14a5 5 0 0 0 10 0v-14z"/>
              <path class="d" style="--t:3.6s;--dl:-2s" d="M122 9v26a6 6 0 0 0 12 0v-26z"/>
              <path class="d" style="--t:2.8s;--dl:-.8s" d="M161 9v8a5 5 0 0 0 10 0v-8z"/>
              <path class="d" style="--t:3.4s;--dl:-1.6s" d="M199 9v12a5 5 0 0 0 10 0v-12z"/>
              <path class="d" style="--t:2.9s;--dl:-.3s" d="M235 9v8a5 5 0 0 0 10 0v-8z"/>
              <path class="d" style="--t:3.8s;--dl:-2.4s" d="M268 9v24a6 6 0 0 0 12 0v-24z"/>
              <path class="d" style="--t:3s;--dl:-1s" d="M307 9v14a5 5 0 0 0 10 0v-14z"/>
              <path class="d d--feed" style="--dd:2.1s" d="M340 9v18a6 6 0 0 0 12 0v-18z"/>
              <path class="d" style="--t:3.3s;--dl:-1.8s" d="M379 9v22a5 5 0 0 0 10 0v-22z"/>
            </g>
            <path d="M0 4H400" stroke="rgba(255,255,255,.14)" stroke-width="2"/>
          </svg>
        </div>
      </div>

      <div class="start-inner">
        <img class="logo" src="{{ asset('game/assets/logo.png') }}" alt="Zara — Cup for any vibe">
        <div class="title-wrap">
          <svg class="rays rays--l" viewBox="0 0 40 40" aria-hidden="true"><g stroke="#ffc928" stroke-width="5" stroke-linecap="round"><path d="M30 8 22 18"/><path d="M8 20h12"/><path d="M28 32l-8-6"/></g></svg>
          <h1 class="title" aria-label="Tower Challenge"><span class="line" aria-hidden="true">Tower</span><span class="line line--gold" aria-hidden="true">Challenge</span></h1>
          <svg class="rays rays--r" viewBox="0 0 40 40" aria-hidden="true"><g stroke="#ffc928" stroke-width="5" stroke-linecap="round"><path d="M10 8l8 10"/><path d="M32 20H20"/><path d="M12 32l8-6"/></g></svg>
        </div>
        <p class="tagline enter" style="--e:.8s">How high can you <b>stack?</b></p>

        <div class="collage" id="collage" aria-hidden="true"></div>

        <form id="start-form" class="start-form" novalidate>
          <label class="field enter" id="name-field" style="--e:1.05s">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-9 2.2-9 5.5V22h18v-2.5C21 16.2 16.4 14 12 14Z"/></svg>
            <input id="nickname" type="text" maxlength="20" placeholder="Enter your name" autocomplete="name" enterkeyhint="next" aria-label="Name" spellcheck="false">
            <span class="field-msg" aria-live="polite"></span>
          </label>
          <label class="field enter" id="phone-field" style="--e:1.15s">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8a15.1 15.1 0 0 0 6.6 6.6l2.2-2.2a1 1 0 0 1 1-.25c1.1.37 2.32.57 3.6.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1L6.6 10.8Z"/></svg>
            <input id="phone" type="tel" inputmode="tel" maxlength="16" placeholder="Mobile (07X XXX XXXX)" autocomplete="tel" enterkeyhint="go" aria-label="Mobile number">
            <span class="field-msg" aria-live="polite"></span>
          </label>
          <div class="play-wrap">
            <button type="submit" class="btn btn--red btn--play">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5v17a1 1 0 0 0 1.5.86l14-8.5a1 1 0 0 0 0-1.72l-14-8.5A1 1 0 0 0 6 3.5Z"/></svg>
              PLAY
            </button>
          </div>
          <p class="privacy-note enter" style="--e:1.3s">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.4 9.6 8 11 4.6-1.4 8-6 8-11V5l-8-3Zm-1.2 14.2-3.5-3.5 1.4-1.4 2.1 2.1 4.7-4.7 1.4 1.4-6.1 6.1Z"/></svg>
            <span>{{ $settings['privacy_summary'] }} <button type="button" class="privacy-link" id="btn-privacy">Privacy notice</button></span>
          </p>
        </form>

        <!-- shown instead of the form while the admin has playing disabled (Admin → Settings) -->
        <div class="closed-card" id="closed-card" role="status" aria-live="polite">
          <span class="closed-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 1 1 6 0v3H9Z"/></svg></span>
          <h2 class="closed-title" id="closed-title">{{ $status['title'] }}</h2>
          <p class="closed-message" id="closed-message" @if (blank($status['message'])) hidden @endif>{{ $status['message'] }}</p>
        </div>

        <div class="start-meta enter" style="--e:1.4s">
          <span class="best-chip" id="best-chip" hidden>
            <svg viewBox="0 0 48 40" aria-hidden="true"><path d="M5 13l10 9 9-15 9 15 10-9-4 21H9L5 13Z" fill="#f8bf26" stroke="#c98a0b" stroke-width="2" stroke-linejoin="round"/><rect x="9" y="33" width="30" height="5" rx="2" fill="#e09c12"/></svg>
            Best <b id="best-val">0</b>
          </span>
          <button type="button" class="link-btn" id="btn-lb-start">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10v2h4v3a5 5 0 0 1-4.6 5A5 5 0 0 1 13 15.9V18h3v3H8v-3h3v-2.1A5 5 0 0 1 7.6 12 5 5 0 0 1 3 7V4h4V2Zm0 4H5v1a3 3 0 0 0 2 2.8V6Zm10 0v3.8A3 3 0 0 0 19 7V6h-2Z"/></svg>
            Leaderboard
          </button>
        </div>
        <p class="tip enter" id="tip" style="--e:1.6s">Tap anywhere to drop the pack</p>
      </div>

      <div class="waves" aria-hidden="true">
        <svg class="wave wave--back" viewBox="0 0 800 120" preserveAspectRatio="none"><defs><linearGradient id="chocA" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8f4d22"/><stop offset="1" stop-color="#4a220c"/></linearGradient></defs><path d="M0 45C50 15 150 75 200 45C250 15 350 75 400 45C450 15 550 75 600 45C650 15 750 75 800 45V120H0Z" fill="url(#chocA)"/><path d="M0 45C50 15 150 75 200 45C250 15 350 75 400 45C450 15 550 75 600 45C650 15 750 75 800 45" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="3" vector-effect="non-scaling-stroke"/></svg>
        <svg class="wave wave--front" viewBox="0 0 800 80" preserveAspectRatio="none"><path d="M0 30C50 8 150 52 200 30C250 8 350 52 400 30C450 8 550 52 600 30C650 8 750 52 800 30V80H0Z" fill="#fff8ec"/></svg>
      </div>
    </section>

    <!-- ============ 2. GAME ============ -->
    <section id="screen-game" class="screen screen--game" aria-label="Game">
      <canvas id="game"></canvas>

      <header class="hud" id="hud">
        <div class="hud-row">
          <img class="logo logo--sm" src="{{ asset('game/assets/logo-badge.png') }}" alt="Zara">
          <div class="hud-level" id="level-box" role="status" aria-label="Level 1">
            <span class="hud-level-label" aria-hidden="true">Level</span>
            <b class="hud-level-num" id="level" aria-hidden="true">1</b>
            <span class="speed-gauge" id="speed-gauge" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i></span>
          </div>
          <button type="button" class="pause-btn" id="btn-pause" aria-label="Pause">
            <svg viewBox="0 0 24 24"><rect x="5" y="3" width="5" height="18" rx="1.5"/><rect x="14" y="3" width="5" height="18" rx="1.5"/></svg>
          </button>
        </div>
        <div class="hud-row">
          <div class="hud-box hud-box--lives" id="lives-box"><span class="hud-label">Lives</span><span class="lives" id="lives" role="img" aria-label="Lives"></span></div>
          <div class="hud-box hud-box--score" id="score-box"><span class="hud-label">Score</span><span class="hud-val" id="score">0</span></div>
        </div>
      </header>

      <div class="tap-hint" id="tap-hint">
        <svg viewBox="0 0 48 48" aria-hidden="true"><g fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" opacity=".75"><path d="M14 12a10 10 0 0 1 14 0"/><path d="M9.5 7.5a16 16 0 0 1 23 0"/></g><path fill="#fff" d="M18 19a3 3 0 0 1 6 0v9l1.2-.2V27a3 3 0 0 1 6 0v1.4a3 3 0 0 1 5.8 1.2V37c0 6-4 10-10 10h-2.4c-4 0-6.5-1.5-9-5l-5-7a3 3 0 0 1 4.5-3.8L18 34V19Z"/></svg>
        <span>Tap to drop<br>the pack!</span>
      </div>

      <div class="countdown" id="countdown" aria-live="assertive"></div>
      <div class="banner" id="banner">OUT OF<br>LIVES!</div>

      <div class="overlay" id="pause-overlay">
        <div class="card" role="dialog" aria-modal="true" aria-labelledby="pause-title">
          <h2 id="pause-title">Paused</h2>
          <button type="button" class="btn btn--red btn--md" id="btn-resume">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5v17a1 1 0 0 0 1.5.86l14-8.5a1 1 0 0 0 0-1.72l-14-8.5A1 1 0 0 0 6 3.5Z"/></svg>
            Resume
          </button>
          <button type="button" class="btn btn--outline" id="btn-restart">Restart</button>
          <button type="button" class="btn btn--outline" id="btn-sound">Sound: On</button>
          <button type="button" class="link-btn" id="btn-quit">Quit to menu</button>
        </div>
      </div>
    </section>

    <!-- ============ 3. GAME OVER ============ -->
    <section id="screen-over" class="screen screen--cream" aria-label="Results">
      <div class="decor" aria-hidden="true">
        <i class="bean" style="top:6%;left:7%;--r:20deg"></i>
        <i class="bean" style="top:30%;right:4%;--r:-30deg;animation-delay:-1.2s"></i>
        <i class="leaf" style="top:3%;right:10%;--r:-15deg;animation-delay:-2s"></i>
        <i class="choc" style="top:44%;left:2%;--r:-12deg;animation-delay:-.8s"></i>
        <i class="pod" style="top:16%;right:2%;--r:30deg;animation-delay:-2.2s"></i>
      </div>

      <div class="over-inner">
        <img class="logo logo--over" src="{{ asset('game/assets/logo.png') }}" alt="Zara — Cup for any vibe">
        <h2 class="gameover-title">Game Over!</h2>

        <div class="score-card">
          <span class="new-best" id="new-best" hidden>New Best!</span>
          <div class="lbl">Your Score</div>
          <div class="val" id="over-score">0</div>
        </div>

        <div class="result-pills">
          <div class="height-pill">
            <svg viewBox="0 0 48 40" aria-hidden="true"><path d="M5 13l10 9 9-15 9 15 10-9-4 21H9L5 13Z" fill="#f8bf26" stroke="#c98a0b" stroke-width="2" stroke-linejoin="round"/><rect x="9" y="33" width="30" height="5" rx="2" fill="#e09c12"/><circle cx="5" cy="12" r="3.2" fill="#f8bf26"/><circle cx="24" cy="6" r="3.2" fill="#f8bf26"/><circle cx="43" cy="12" r="3.2" fill="#f8bf26"/></svg>
            <div>
              <div class="t1">Tower Height</div>
              <div class="t2" id="over-height">0 Packs</div>
            </div>
          </div>
          <!-- the leaderboard place this result gives, from /game/finish -->
          <div class="height-pill rank-pill" id="rank-pill">
            <svg viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="22" r="14" fill="#f8bf26" stroke="#c98a0b" stroke-width="2"/><circle cx="20" cy="22" r="9.5" fill="none" stroke="#fff3c4" stroke-width="2"/><path d="M13 2h5l3 7h-5Zm14 0h-5l-3 7h5Z" fill="#e4252b"/><path d="m20 16 1.8 3.7 4 .6-2.9 2.8.7 4L20 25.2l-3.6 1.9.7-4-2.9-2.8 4-.6Z" fill="#fff"/></svg>
            <div>
              <div class="t1">Your Rank</div>
              <div class="t2"><span id="over-rank">…</span><small id="over-rank-of"></small></div>
            </div>
          </div>
        </div>

        <div class="stats">
          <span>Best: <b id="over-best">0</b></span>
          <span>Perfect stacks: <b id="over-perfects">0</b></span>
        </div>

        <div class="over-actions">
          <button type="button" class="btn btn--red btn--submit" id="btn-submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10v2h4v3a5 5 0 0 1-4.6 5A5 5 0 0 1 13 15.9V18h3v3H8v-3h3v-2.1A5 5 0 0 1 7.6 12 5 5 0 0 1 3 7V4h4V2Zm0 4H5v1a3 3 0 0 0 2 2.8V6Zm10 0v3.8A3 3 0 0 0 19 7V6h-2Z"/></svg>
            <span id="submit-label">Submit to<br>Leaderboard</span>
          </button>
          <button type="button" class="btn btn--outline" id="btn-again">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12a8 8 0 1 1-2.34-5.66"/><path d="M20 4v5h-5"/></svg>
            PLAY AGAIN
          </button>
          <button type="button" class="link-btn" id="btn-menu">Edit name or number</button>
        </div>
      </div>

      <div class="waves" aria-hidden="true">
        <svg class="wave wave--back" viewBox="0 0 800 120" preserveAspectRatio="none"><defs><linearGradient id="chocB" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#8f4d22"/><stop offset="1" stop-color="#4a220c"/></linearGradient></defs><path d="M0 45C50 15 150 75 200 45C250 15 350 75 400 45C450 15 550 75 600 45C650 15 750 75 800 45V120H0Z" fill="url(#chocB)"/><path d="M0 45C50 15 150 75 200 45C250 15 350 75 400 45C450 15 550 75 600 45C650 15 750 75 800 45" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="3" vector-effect="non-scaling-stroke"/></svg>
        <svg class="wave wave--front" viewBox="0 0 800 80" preserveAspectRatio="none"><path d="M0 30C50 8 150 52 200 30C250 8 350 52 400 30C450 8 550 52 600 30C650 8 750 52 800 30V80H0Z" fill="#fff8ec"/></svg>
      </div>
    </section>

    <!-- ============ LEADERBOARD ============ -->
    <div class="modal" id="lb-modal">
      <div class="card card--lb" role="dialog" aria-modal="true" aria-labelledby="lb-title">
        <h2 id="lb-title">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 2h10v2h4v3a5 5 0 0 1-4.6 5A5 5 0 0 1 13 15.9V18h3v3H8v-3h3v-2.1A5 5 0 0 1 7.6 12 5 5 0 0 1 3 7V4h4V2Zm0 4H5v1a3 3 0 0 0 2 2.8V6Zm10 0v3.8A3 3 0 0 0 19 7V6h-2Z"/></svg>
          Leaderboard
        </h2>
        <ol class="lb-list" id="lb-list"></ol>
        <button type="button" class="btn btn--outline" id="lb-close">Close</button>
      </div>
    </div>

    <!-- ============ PRIVACY NOTICE (Admin → Settings) ============ -->
    <div class="modal" id="privacy-modal">
      <div class="card card--privacy" role="dialog" aria-modal="true" aria-labelledby="privacy-title">
        <h2 id="privacy-title">Privacy notice</h2>
        <div class="privacy-body">
          @foreach (preg_split('/\R\s*\R/', trim($settings['privacy_notice'])) as $section)
            @php($lines = preg_split('/\R/', trim($section)))
            @if (count($lines) > 1)
              <h3>{{ $lines[0] }}</h3>
              <p>{!! nl2br(e(implode("\n", array_slice($lines, 1)))) !!}</p>
            @else
              <p>{{ $lines[0] }}</p>
            @endif
          @endforeach
        </div>
        <button type="button" class="btn btn--outline" id="privacy-close">Close</button>
      </div>
    </div>

    <div class="rotate" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
      Please rotate your phone to portrait
    </div>
  </div>

  <script>window.ZARA = @json($zara);</script>
  <script src="{{ asset('game/game.js') }}?v={{ filemtime(public_path('game/game.js')) }}"></script>
</body>
</html>
