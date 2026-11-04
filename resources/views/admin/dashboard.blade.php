@extends('admin.layout')

@section('title', 'Dashboard')

@push('styles')
<style>
  .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px; }
  .stat { padding: 14px 18px; }
  .stat span { display: block; color: var(--muted); font-weight: 500; font-size: 14px; }
  .stat b { font-size: 28px; color: var(--brown-dark); }

  .bar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 12px; }
  .tabs { display: flex; gap: 6px; flex-wrap: wrap; }
  .tabs a { padding: 8px 16px; border-radius: 999px; text-decoration: none; font-weight: 600; color: var(--brown); background: #fff; border: 2px solid var(--line); }
  .tabs a.on { background: var(--brown); border-color: var(--brown); color: #fff; }
  .search { display: flex; gap: 8px; flex: 1; max-width: 380px; }

  .table-wrap { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th, td { padding: 11px 14px; text-align: left; border-bottom: 1px solid var(--line); white-space: nowrap; }
  th { color: var(--muted); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: .03em; }
  td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
  tr:last-child td { border-bottom: 0; }
  .rank { display: inline-grid; place-items: center; width: 28px; height: 28px; border-radius: 50%; background: #efe1cc; font-weight: 700; }
  .rank.r1 { background: linear-gradient(#ffe27a, #f0b323); }
  .rank.r2 { background: linear-gradient(#f1f1f1, #bfc4c9); }
  .rank.r3 { background: linear-gradient(#f3c299, #c8783c); }
  .yes { color: #1f8a3a; font-weight: 600; }
  .muted { color: var(--muted); }
  .empty { padding: 36px; text-align: center; color: var(--muted); }

  .pager { margin-top: 14px; }
  .pager nav { display: flex; justify-content: center; }
  .pager .pagination { display: flex; gap: 4px; list-style: none; padding: 0; margin: 0; flex-wrap: wrap; }
  .pager .page-item > * { display: block; min-width: 36px; padding: 6px 10px; text-align: center; border-radius: 10px; background: #fff; border: 2px solid var(--line); text-decoration: none; }
  .pager .page-item.active > * { background: var(--brown); border-color: var(--brown); color: #fff; }
  .pager .page-item.disabled > * { opacity: .45; }
</style>
@endpush

@section('body')
@php($local = fn ($t) => $t ? $t->copy()->timezone(config('game.timezone'))->format('d M Y, g:i a') : '—')
<div class="wrap">
  @include('admin.header', ['title' => 'Tower Challenge admin'])

  <section class="stats">
    <div class="card stat"><span>Players</span><b>{{ number_format($stats['players']) }}</b></div>
    <div class="card stat"><span>Games played</span><b>{{ number_format($stats['games']) }}</b></div>
    <div class="card stat"><span>Games today</span><b>{{ number_format($stats['today']) }}</b></div>
    <div class="card stat"><span>Top score</span><b>{{ number_format($stats['top']) }}</b></div>
  </section>

  <div class="bar">
    <nav class="tabs">
      @foreach (['leaderboard' => 'Leaderboard', 'players' => 'Players', 'games' => 'Games'] as $key => $label)
        <a href="{{ route('admin.dashboard', ['tab' => $key]) }}" @class(['on' => $tab === $key])>{{ $label }}</a>
      @endforeach
    </nav>
    <form class="search" method="GET" action="{{ route('admin.dashboard') }}">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <input type="search" name="search" value="{{ $search }}" placeholder="Search name or mobile">
      <button class="btn btn--ghost" type="submit">Search</button>
    </form>
  </div>

  <div class="card table-wrap">
    @if ($rows->isEmpty())
      <p class="empty">{{ $search !== '' ? 'No matches for “'.$search.'”.' : 'Nothing here yet.' }}</p>
    @elseif ($tab === 'leaderboard')
      <table>
        <thead><tr><th>Rank</th><th>Name</th><th>Mobile</th><th class="num">Best score</th><th class="num">Tower</th><th class="num">Games</th><th>Best score at</th></tr></thead>
        <tbody>
          @foreach ($rows as $player)
            @php($rank = $search === '' ? $rows->firstItem() + $loop->index : $player->rank())
            <tr>
              <td><span @class(['rank', 'r'.$rank => $rank <= 3])>{{ $rank }}</span></td>
              <td>{{ $player->name }}</td>
              <td>{{ $player->formattedPhone() }}</td>
              <td class="num"><b>{{ number_format($player->best_score) }}</b></td>
              <td class="num">{{ $player->best_height }}</td>
              <td class="num">{{ $player->games_played }}</td>
              <td class="muted">{{ $local($player->best_at) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @elseif ($tab === 'players')
      <table>
        <thead><tr><th>Name</th><th>Mobile</th><th class="num">Best score</th><th class="num">Games</th><th>Last active</th><th>First played</th></tr></thead>
        <tbody>
          @foreach ($rows as $player)
            <tr>
              <td>{{ $player->name }}</td>
              <td>{{ $player->formattedPhone() }}</td>
              <td class="num">{{ $player->best_at ? number_format($player->best_score) : '—' }}</td>
              <td class="num">{{ $player->games_played }}</td>
              <td class="muted">{{ $local($player->updated_at) }}</td>
              <td class="muted">{{ $local($player->created_at) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @else
      <table>
        <thead><tr><th>Finished</th><th>Name</th><th>Mobile</th><th class="num">Score</th><th class="num">Tower</th><th class="num">Perfects</th><th>Submitted</th></tr></thead>
        <tbody>
          @foreach ($rows as $play)
            <tr>
              <td class="muted">{{ $local($play->finished_at) }}</td>
              <td>{{ $play->player->name }}</td>
              <td>{{ $play->player->formattedPhone() }}</td>
              <td class="num"><b>{{ number_format($play->score) }}</b></td>
              <td class="num">{{ $play->height }}</td>
              <td class="num">{{ $play->perfects }}</td>
              <td>@if ($play->submitted_at)<span class="yes">✓ Yes</span>@else<span class="muted">No</span>@endif</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>

  <div class="pager">{{ $rows->links('pagination::bootstrap-4') }}</div>
</div>
@endsection
