<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex">
  <title>@yield('title') · Zara Tower Challenge</title>
  <link rel="icon" type="image/png" href="{{ asset('game/assets/logo-badge.png') }}">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --cream: #fdf3e3; --card: #fff; --line: #f0dcc0; --brown: #6b3315; --brown-dark: #3f1c08;
      --muted: #9a7a5c; --red: #e4252b; --red-dark: #a8121a; --gold: #f4b52e;
    }
    * { box-sizing: border-box; }
    body { margin: 0; min-height: 100vh; background: var(--cream); color: var(--brown-dark); font: 400 15px/1.45 'Fredoka', system-ui, sans-serif; }
    a { color: inherit; }
    .wrap { max-width: 1100px; margin: 0 auto; padding: 20px 16px 40px; }

    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; border: 0; border-radius: 999px; cursor: pointer; font: 600 14px 'Fredoka', sans-serif; text-decoration: none; }
    .btn--red { background: linear-gradient(#f0453d, #c3151d); color: #fff; box-shadow: 0 3px 0 var(--red-dark); }
    .btn--ghost { background: #fff; color: var(--brown); border: 2px solid var(--line); }
    .btn:active { transform: translateY(2px); }

    .card { background: var(--card); border: 2px solid var(--line); border-radius: 18px; box-shadow: 0 6px 18px rgba(110, 60, 20, .08); }
    input[type=text], input[type=search], input[type=password] {
      width: 100%; padding: 11px 16px; border: 2px solid var(--line); border-radius: 999px; background: #fff;
      font: 500 15px 'Fredoka', sans-serif; color: var(--brown-dark); outline: 0;
    }
    input:focus { border-color: var(--gold); }
    .error { color: var(--red); font-weight: 600; font-size: 14px; }

    header.top { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
    header.top img { height: 44px; display: block; }
    header.top h1 { margin: 0; font-size: 22px; color: var(--brown); }
    header.top form { margin: 0; }
    .top-nav { display: flex; gap: 4px; margin-left: auto; }
    .top-nav a { padding: 8px 14px; border-radius: 999px; text-decoration: none; font-weight: 600; color: var(--brown); }
    .top-nav a:hover { background: #f7e6cc; }
    .top-nav a.on { background: var(--brown); color: #fff; }
    .game-pill { display: inline-flex; align-items: center; gap: 7px; padding: 5px 12px; border-radius: 999px; background: #e3f6e6; color: #1f7a35; font-weight: 600; font-size: 13px; text-decoration: none; }
    .game-pill i { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
    .game-pill--off { background: #fde4e4; color: var(--red-dark); }
  </style>
  @stack('styles')
</head>
<body>
  @yield('body')
</body>
</html>
