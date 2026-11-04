@extends('admin.layout')

@section('title', 'Admin login')

@push('styles')
<style>
  .login { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
  .login .card { width: min(100%, 360px); padding: 28px 24px; text-align: center; display: grid; gap: 14px; }
  .login img { height: 84px; margin: 0 auto; }
  .login h1 { margin: 0; font-size: 22px; color: var(--brown); }
  .login .btn { justify-content: center; width: 100%; padding: 12px; font-size: 16px; }
  .notice { background: #fff4c4; border-radius: 12px; padding: 10px 12px; font-size: 14px; color: var(--brown); }
</style>
@endpush

@section('body')
<main class="login">
  <form class="card" method="POST" action="{{ route('admin.login.post') }}">
    @csrf
    <img src="{{ asset('game/assets/logo.png') }}" alt="Zara">
    <h1>Tower Challenge admin</h1>

    @unless ($configured)
      <p class="notice">The admin password isn't set yet. Add <b>ADMIN_PASSWORD</b> to the <b>.env</b> file.</p>
    @endunless

    <input type="password" name="password" placeholder="Password" autocomplete="current-password" required autofocus>
    @error('password') <p class="error">{{ $message }}</p> @enderror

    <button class="btn btn--red" type="submit">Log in</button>
  </form>
</main>
@endsection
