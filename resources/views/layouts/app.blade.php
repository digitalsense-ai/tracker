<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tracker</title>
  <link rel="stylesheet" href="/css/tracker-light.css">
</head>
<body class="tracker">
  <div class="container">
    <ul class="nav top">
      <li><a href="/status">Status</a></li>
      <li><a href="/dashboard">Dashboard</a></li>
      <li><a href="/kpi">KPI</a></li>
      <li><a href="/results">Results</a></li>
      <li><a href="/backtest">Backtest</a></li>
      <li><a href="/explainer-flow">Explainer Flow</a></li>
      <li><a href="/signals">Signals</a></li>
      <li><a href="/settings">Settings</a></li>
      <li><a href="/profiles">Profiles</a></li>
    </ul>
    @yield('content')
  </div>
</body>
</html>
