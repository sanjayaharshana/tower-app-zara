@extends('admin.layout')

@section('title', 'Settings')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Lilita+One&display=swap" rel="stylesheet">
<style>
  .settings { display: grid; gap: 16px; max-width: 860px; }
  .panel { padding: 20px 22px; }
  .panel h2 { margin: 0 0 4px; font-size: 18px; color: var(--brown); }
  .muted { color: var(--muted); }
  .panel > .muted { margin: 0 0 16px; }

  .flash { padding: 10px 16px; border-radius: 14px; background: #e3f6e6; color: #1f7a35; font-weight: 600; margin-bottom: 16px; max-width: 860px; }

  .switch-row { display: flex; align-items: center; gap: 18px; cursor: pointer; }
  .switch-row b { display: block; font-size: 16px; }
  .switch-row .muted { display: block; font-size: 14px; margin-top: 2px; }
  .switch-text { flex: 1; }
  .switch { appearance: none; -webkit-appearance: none; flex: none; position: relative; width: 56px; height: 32px; margin: 0; border-radius: 999px; background: #ddcbb1; cursor: pointer; transition: background .2s; }
  .switch::before { content: ''; position: absolute; top: 4px; left: 4px; width: 24px; height: 24px; border-radius: 50%; background: #fff; box-shadow: 0 2px 4px rgba(0, 0, 0, .2); transition: transform .2s; }
  .switch:checked { background: var(--red); }
  .switch:checked::before { transform: translateX(24px); }
  .switch:focus-visible { outline: 3px solid var(--gold); outline-offset: 2px; }
  .state { display: inline-block; margin-top: 12px; padding: 4px 12px; border-radius: 999px; font-weight: 600; font-size: 13px; background: #e3f6e6; color: #1f7a35; }
  .state--off { background: #fde4e4; color: var(--red-dark); }
  .state--muted { background: #f3e8d8; color: var(--muted); }

  .number-field { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 14px; }
  .number-field .field-label { margin: 0; }
  .number-field input { width: 110px; padding: 10px 16px; border: 2px solid var(--line); border-radius: 999px; background: #fff; font: 600 20px 'Fredoka', sans-serif; color: var(--brown-dark); text-align: center; outline: 0; }
  .number-field input:focus { border-color: var(--gold); }

  .difficulty { margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line); transition: opacity .2s; }
  .difficulty.is-off { opacity: .45; }
  .difficulty-head { display: flex; align-items: baseline; justify-content: space-between; }
  .difficulty-head output { font-weight: 700; color: var(--red-dark); }
  .difficulty input[type=range] { width: 100%; margin: 6px 0 2px; accent-color: var(--red); cursor: pointer; }
  .difficulty-ends { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; color: var(--muted); }
  .difficulty p { margin: 8px 0 0; font-size: 14px; }
  .speed-plan { padding: 10px 14px; border-radius: 14px; background: #fff6e6; color: var(--brown-dark); font-weight: 500; }

  .cols { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }
  @media (max-width: 720px) { .cols { grid-template-columns: 1fr; } }
  .field-label { display: block; font-weight: 600; margin: 0 0 6px; }
  .field-group { margin-bottom: 14px; }
  .field-group .count { float: right; font-weight: 500; font-size: 13px; color: var(--muted); }
  textarea { width: 100%; padding: 12px 16px; border: 2px solid var(--line); border-radius: 18px; background: #fff; font: 500 15px/1.45 'Fredoka', sans-serif; color: var(--brown-dark); outline: 0; resize: vertical; }
  textarea:focus { border-color: var(--gold); }

  .preview-label { display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: var(--muted); margin-bottom: 6px; }
  .preview { padding: 22px 16px; border-radius: 18px; background: radial-gradient(130% 90% at 50% 10%, #fffdf6, #fdeed5 55%, #f7d9b0); }
  .closed-preview { display: flex; flex-direction: column; align-items: center; gap: 6px; padding: 18px 16px; text-align: center; background: #fff8ec; border: 2.5px solid #e8cfae; border-radius: 22px; box-shadow: 0 3px 0 #ead6ba; }
  .closed-preview .icon { display: grid; place-items: center; width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(#f6493f, #c3151d); box-shadow: 0 3px 0 var(--red-dark); }
  .closed-preview .icon svg { width: 22px; height: 22px; fill: #fff; }
  .closed-preview h3 { margin: 4px 0 0; font: 400 24px/1.1 'Lilita One', 'Fredoka', sans-serif; color: var(--brown); overflow-wrap: anywhere; }
  .closed-preview p { margin: 0; font-weight: 500; color: #8a4a22; white-space: pre-line; overflow-wrap: anywhere; }

  .actions { display: flex; justify-content: flex-end; }
  .actions .btn { padding: 11px 26px; font-size: 15px; }
</style>
@endpush

@section('body')
<div class="wrap">
  @include('admin.header', ['title' => 'Settings', 'gameOpen' => ! $settings['playing_disabled']])

  @if (session('status'))
    <p class="flash" role="status">{{ session('status') }}</p>
  @endif

  <form class="settings" method="POST" action="{{ route('admin.settings.update') }}">
    @csrf
    @method('PUT')

    <section class="card panel">
      <h2>Game access</h2>
      <label class="switch-row">
        <span class="switch-text">
          <b>Disable playing</b>
          <span class="muted">Players see the message below on the start screen instead of the Play button, and no new games can start. Games already in progress can still finish and submit their score. Open start screens pick up the change within 30 seconds.</span>
        </span>
        <input type="hidden" name="playing_disabled" value="0">
        <input class="switch" type="checkbox" name="playing_disabled" value="1" id="playing-disabled"
               @checked(old('playing_disabled', $settings['playing_disabled']))>
      </label>
      <span @class(['state', 'state--off' => $settings['playing_disabled']])>
        {{ $settings['playing_disabled'] ? 'Right now: playing is disabled' : 'Right now: game is open to players' }}
      </span>
    </section>

    <section class="card panel">
      <h2>Message while playing is disabled</h2>
      <p class="muted">Shown on the game's start screen in place of the name, number and Play button.</p>
      <div class="cols">
        <div>
          <div class="field-group">
            <label class="field-label" for="closed-title">Title <span class="count" data-count-for="closed-title"></span></label>
            <input type="text" id="closed-title" name="closed_title" maxlength="60" required
                   value="{{ old('closed_title', $settings['closed_title']) }}">
            @error('closed_title') <p class="error">{{ $message }}</p> @enderror
          </div>
          <div class="field-group">
            <label class="field-label" for="closed-message">Message <span class="count" data-count-for="closed-message"></span></label>
            <textarea id="closed-message" name="closed_message" maxlength="300" rows="4">{{ old('closed_message', $settings['closed_message']) }}</textarea>
            @error('closed_message') <p class="error">{{ $message }}</p> @enderror
          </div>
        </div>
        <div>
          <span class="preview-label">Preview</span>
          <div class="preview">
            <div class="closed-preview">
              <span class="icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5Zm-3 8V7a3 3 0 1 1 6 0v3H9Z"/></svg></span>
              <h3 id="preview-title"></h3>
              <p id="preview-message"></p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="card panel">
      <h2>Lives</h2>
      <p class="muted">Games have no time limit. Players start with this many lives, and every fall costs one: a pack that misses, a pack that tips off the edge, or the tower toppling. The game ends when the last life is gone. Changes apply from each player's next game.</p>
      <div class="number-field">
        <label class="field-label" for="lives">Lives per game</label>
        <input type="number" id="lives" name="lives" min="1" max="{{ $maxLives }}" step="1" required
               value="{{ old('lives', $settings['lives']) }}">
        <span class="muted">1 to {{ $maxLives }}</span>
      </div>
      @error('lives') <p class="error">{{ $message }}</p> @enderror
    </section>

    <section class="card panel">
      <h2>Speed levels</h2>
      <p class="muted">The moving pack speeds up step by step. Every time a player stacks the set number of packs they go up a level, and the pack moves faster until it reaches top speed. A level is never lost, even when the tower falls. Changes apply from each player's next game.</p>
      <div class="number-field">
        <label class="field-label" for="packs-per-level">Level up every</label>
        <input type="number" id="packs-per-level" name="packs_per_level" min="1" max="{{ $maxPacksPerLevel }}" step="1" required
               value="{{ old('packs_per_level', $settings['packs_per_level']) }}">
        <span class="muted">stacked packs (1 to {{ $maxPacksPerLevel }})</span>
      </div>
      @error('packs_per_level') <p class="error">{{ $message }}</p> @enderror

      <div class="difficulty">
        <div class="difficulty-head">
          <label class="field-label" for="speed-difficulty">Speed difficulty</label>
          <output id="speed-name" for="speed-difficulty"></output>
        </div>
        <input type="range" id="speed-difficulty" name="speed_difficulty" min="1" max="{{ count($speedLevels) }}" step="1"
               value="{{ old('speed_difficulty', $settings['speed_difficulty']) }}">
        <div class="difficulty-ends"><span>{{ $speedLevels[1]['name'] }}</span><span>{{ $speedLevels[count($speedLevels)]['name'] }}</span></div>
        <p class="muted" id="speed-desc"></p>
        <p class="speed-plan" id="speed-plan"></p>
        @error('speed_difficulty') <p class="error">{{ $message }}</p> @enderror
      </div>

      <span class="state">
        Right now: {{ $speedLevels[$settings['speed_difficulty']]['name'] }} · level up every {{ $settings['packs_per_level'] }} {{ $settings['packs_per_level'] === 1 ? 'pack' : 'packs' }}
      </span>
    </section>

    <section class="card panel">
      <h2>Tower physics</h2>
      <label class="switch-row">
        <span class="switch-text">
          <b>Enable falling physics</b>
          <span class="muted">The tower sways smoothly and leans toward its heavier side. A pack that lands too far over the edge tips off, and when the weight above any pack hangs too far past it, the tower falls down from there. When off, the game plays as normal. Changes apply from each player's next round.</span>
        </span>
        <input type="hidden" name="physics_enabled" value="0">
        <input class="switch" type="checkbox" name="physics_enabled" value="1" id="physics-enabled"
               @checked(old('physics_enabled', $settings['physics_enabled']))>
      </label>

      <div @class(['difficulty', 'is-off' => ! old('physics_enabled', $settings['physics_enabled'])]) id="difficulty">
        <div class="difficulty-head">
          <label class="field-label" for="physics-difficulty">Difficulty</label>
          <output id="difficulty-name" for="physics-difficulty"></output>
        </div>
        <input type="range" id="physics-difficulty" name="physics_difficulty" min="1" max="{{ count($levels) }}" step="1"
               value="{{ old('physics_difficulty', $settings['physics_difficulty']) }}">
        <div class="difficulty-ends"><span>{{ $levels[1][0] }}</span><span>{{ $levels[count($levels)][0] }}</span></div>
        <p class="muted" id="difficulty-desc"></p>
        @error('physics_difficulty') <p class="error">{{ $message }}</p> @enderror
      </div>

      <span @class(['state', 'state--muted' => ! $settings['physics_enabled']])>
        Right now: {{ $settings['physics_enabled'] ? 'physics on · '.$levels[$settings['physics_difficulty']][0] : 'physics off, normal game' }}
      </span>
    </section>

    <div class="actions">
      <button class="btn btn--red" type="submit">Save settings</button>
    </div>
  </form>
</div>

<script>
  // live preview and character counts for the closed-screen texts
  (() => {
    const title = document.getElementById('closed-title');
    const message = document.getElementById('closed-message');
    const sync = () => {
      document.getElementById('preview-title').textContent = title.value.trim() || 'Title';
      const msg = document.getElementById('preview-message');
      msg.textContent = message.value.trim();
      msg.hidden = !message.value.trim();
      for (const input of [title, message]) {
        document.querySelector(`[data-count-for="${input.id}"]`).textContent = `${input.value.length}/${input.maxLength}`;
      }
    };
    title.addEventListener('input', sync);
    message.addEventListener('input', sync);
    sync();
  })();

  // speed difficulty: name, description and how the levels play out follow the inputs
  (() => {
    const levels = @json($speedLevels);
    const range = document.getElementById('speed-difficulty');
    const packs = document.getElementById('packs-per-level');
    const sync = () => {
      const level = levels[range.value];
      const every = Math.max(1, parseInt(packs.value, 10) || 1);
      const top = level.step > 0 ? Math.ceil((level.max / level.start - 1) / level.step - 1e-9) + 1 : 1;
      const times = Math.round((level.max / level.start) * 10) / 10;
      document.getElementById('speed-name').textContent = `${range.value} · ${level.name}`;
      document.getElementById('speed-desc').textContent = level.description;
      document.getElementById('speed-plan').textContent =
        `Each level adds ${Math.round(level.step * 100)}% of the starting speed. Top speed (${times}× the start) `
        + `comes at level ${top}, after ${(top - 1) * every} stacked ${(top - 1) * every === 1 ? 'pack' : 'packs'}.`;
    };
    range.addEventListener('input', sync);
    packs.addEventListener('input', sync);
    sync();
  })();

  // difficulty name and description follow the slider; the slider dims while physics is off
  (() => {
    const levels = @json($levels);
    const toggle = document.getElementById('physics-enabled');
    const range = document.getElementById('physics-difficulty');
    const sync = () => {
      const [name, desc] = levels[range.value];
      document.getElementById('difficulty-name').textContent = `${range.value} · ${name}`;
      document.getElementById('difficulty-desc').textContent = desc;
      document.getElementById('difficulty').classList.toggle('is-off', !toggle.checked);
    };
    range.addEventListener('input', sync);
    toggle.addEventListener('change', sync);
    sync();
  })();
</script>
@endsection
