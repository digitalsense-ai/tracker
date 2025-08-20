<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg bg-light border-bottom">
  <div class="container-fluid">
    <a class="navbar-brand fw-semibold" href="{{ url('/') }}">Tracker</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link {{ request()->is('status') ? 'active' : '' }}" href="{{ url('/status') }}">Status</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->is('kpi') ? 'active' : '' }}" href="{{ url('/kpi') }}">KPI</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->is('results') ? 'active' : '' }}" href="{{ url('/results') }}">Results</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->is('backtest') ? 'active' : '' }}" href="{{ url('/backtest') }}">Backtest</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link {{ request()->is('explainer-flow') ? 'active' : '' }}" href="{{ url('/explainer-flow') }}">Explainer Flow</a></li>
      </ul>
    </div>
  </div>
</nav>

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
