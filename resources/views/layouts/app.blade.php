<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>@yield('title','Alpha Arena')</title>
  <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
  <meta name="robots" content="noindex">
  <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
<header>
  <div class="container topbar">
    <div class="brand">
      <div class="dot"></div>
      <div>
        <div class="bold">@yield('header_title','Alpha Arena')</div>
        <div class="small">Mock UI · replace # with live fields</div>
      </div>
    </div>
    <nav class="nav">
      <a href="{{ route('live.index') }}" class="{{ request()->routeIs('live.index') ? 'active' : '' }}">Live</a>
      <a href="{{ route('profiles.leaderboard') }}" class="{{ request()->routeIs('profiles.leaderboard') ? 'active' : '' }}">Leaderboard</a>
      <a href="{{ route('models.index') }}" class="{{ request()->routeIs('models.*') ? 'active' : '' }}">Models</a>
    </nav>
  </div>
</header>

<main class="container">
  @yield('content')
  <div class="footer-note">Deploy-ready · Replace all <b>#</b> with live values.</div>
</main>
<script src="{{ asset('assets/jquery.min.js') }}"></script>
<script src="{{ asset('assets/app.js') }}"></script>
@yield('scripts')
</body>
</html>
