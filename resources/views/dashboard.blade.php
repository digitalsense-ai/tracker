@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="mb-3">Dashboard</h1>
  <p class="text-muted">Coming soon: live watchlist, signals and quick KPIs.</p>

  <div class="row g-3">
    <div class="col-md-4"><div class="card h-100"><div class="card-body">
      <h6 class="text-muted">Today</h6>
      <div class="display-6 fw-bold">{{ $todayTrades ?? 0 }}</div>
      <div class="small text-muted">Trades simulated</div>
    </div></div></div>

    <div class="col-md-4"><div class="card h-100"><div class="card-body">
      <h6 class="text-muted">Win rate (last 5d)</h6>
      <div class="display-6 fw-bold">{{ $winRate5d ?? '—' }}%</div>
    </div></div></div>

    <div class="col-md-4"><div class="card h-100"><div class="card-body">
      <h6 class="text-muted">Net (last 5d)</h6>
      <div class="display-6 fw-bold">${{ isset($net5d) ? number_format($net5d,2) : '—' }}</div>
    </div></div></div>
  </div>

  <hr class="my-4">

  <a class="btn btn-outline-primary me-2" href="{{ url('/kpi') }}">Open KPI</a>
  <a class="btn btn-outline-secondary me-2" href="{{ url('/results') }}">Open Results</a>
  <a class="btn btn-outline-dark" href="{{ url('/explainer-flow') }}">Open Explainer Flow</a>
</div>
@endsection
