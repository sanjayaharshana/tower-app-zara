{{-- Admin page header. Expects $title and $gameOpen. --}}
<header class="top">
  <a href="{{ route('admin.dashboard') }}"><img src="{{ asset('game/assets/logo.png') }}" alt="Zara"></a>
  <h1>{{ $title }}</h1>
  <a href="{{ route('admin.settings') }}" @class(['game-pill', 'game-pill--off' => ! $gameOpen])
     title="{{ $gameOpen ? 'Players can start new games' : 'Playing is disabled' }}">
    <i></i>{{ $gameOpen ? 'Game open' : 'Game closed' }}
  </a>
  <nav class="top-nav">
    <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])>Dashboard</a>
    <a href="{{ route('admin.settings') }}" @class(['on' => request()->routeIs('admin.settings')])>Settings</a>
    <a href="{{ route('leaderboard.screen') }}" target="_blank" rel="noopener" title="Live leaderboard for the 1080×1920 kiosk screen">Kiosk screen ↗</a>
  </nav>
  <a class="btn btn--red" href="{{ route('admin.export') }}">Export players (CSV)</a>
  <form method="POST" action="{{ route('admin.logout') }}">
    @csrf
    <button class="btn btn--ghost" type="submit">Log out</button>
  </form>
</header>
