@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="mb-3">Dashboard</h1>
  <p class="text-muted">Window: {{ $from }} → {{ $to }} (approx. last 5 trading days)</p>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted">Trades today (closed)</div>
      <div class="display-6 fw-bold">{{ $todayTrades }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted">Open now</div>
      <div class="display-6 fw-bold">{{ $openTrades }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted">Win rate (5d)</div>
      <div class="display-6 fw-bold">{{ $winRate5d }}%</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted">Avg R (5d)</div>
      <div class="display-6 fw-bold">{{ $avgR5d }}</div>
    </div></div></div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted">Net (5d)</div>
      <div class="display-6 fw-bold">${{ number_format($net5d,2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted">Fees (5d)</div>
      <div class="display-6 fw-bold">${{ number_format($fees5d,2) }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted">Profit factor (5d)</div>
      <div class="display-6 fw-bold">{{ is_infinite($profitFactor5d) ? '∞' : $profitFactor5d }}</div>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body">
      <div class="text-muted">TP / SL hit (5d)</div>
      <div class="display-6 fw-bold">{{ $tpRate5d }}% / {{ $slRate5d }}%</div>
    </div></div></div>
  </div>

  @if($best || $worst)
  <hr class="my-3">
  <h5>Best / Worst (5d)</h5>
  <ul>
    @if($best)<li><strong>Best:</strong> {{ $best->ticker }} — ${{ number_format($best->net_profit,2) }} ({{ $best->date }})</li>@endif
    @if($worst)<li><strong>Worst:</strong> {{ $worst->ticker }} — ${{ number_format($worst->net_profit,2) }} ({{ $worst->date }})</li>@endif
  </ul>
  @endif

  <hr class="my-3">
  <h5>Recent 10 trades</h5>
  <div class="table-responsive">
    <table class="table table-sm table-striped">
      <thead><tr><th>Date</th><th>Ticker</th><th>Status</th><th>Entry</th><th>SL</th><th>Exit</th><th>Net</th></tr></thead>
      <tbody>
        @forelse($recent as $t)
          <tr>
            <td>{{ $t->date }}</td>
            <td>{{ $t->ticker }}</td>
            <td>{{ $t->status }}</td>
            <td>{{ is_numeric($t->entry_price) ? number_format($t->entry_price,2) : '—' }}</td>
            <td>{{ is_numeric($t->sl_price) ? number_format($t->sl_price,2) : '—' }}</td>
            <td>{{ is_numeric($t->exit_price) ? number_format($t->exit_price,2) : '—' }}</td>
            <td>{{ is_numeric($t->net_profit) ? '$'.number_format($t->net_profit,2) : '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-muted">No trades yet</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($perTicker && count($perTicker))
  <hr class="my-3">
  <h5>By Ticker (5d)</h5>
  <div class="row g-3">
    @foreach($perTicker as $sym => $m)
    <div class="col-md-4">
      <div class="card h-100"><div class="card-body">
        <div class="fw-bold">{{ $sym }}</div>
        <div class="small text-muted">{{ $m['trades'] }} trades · Win {{ $m['winRate'] }}%</div>
        <div class="fs-5">@if($m['net']>=0)+@endif${{ number_format($m['net'],2) }}</div>
      </div></div>
    </div>
    @endforeach
  </div>
  @endif

  <hr class="my-4">
  <a class="btn btn-outline-primary me-2" href="{{ url('/kpi') }}">Open KPI</a>
  <a class="btn btn-outline-secondary me-2" href="{{ url('/results') }}">Open Results</a>
  <a class="btn btn-outline-dark" href="{{ url('/explainer-flow') }}">Open Explainer Flow</a>
</div>
@endsection
