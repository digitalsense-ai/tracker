@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="mb-3">KPIs</h1>

  @php
    $trades = collect($trades ?? []);
    $closed = $trades->filter(function($t){
      return in_array($t->status ?? '', ['closed','SL','TP1','TP2'])
          && is_numeric($t->entry_price ?? null)
          && is_numeric($t->sl_price ?? null)
          && ($t->entry_price > $t->sl_price)
          && is_numeric($t->exit_price ?? null);
    });

    $winCount = $closed->filter(fn($t) => ($t->exit_price > $t->entry_price))->count();
    $tradeCount = $closed->count();
    $winRate = $tradeCount ? round(100 * $winCount / $tradeCount, 1) : 0;

    $avgR = $tradeCount ? round($closed->avg(function($t){
      $risk = ($t->entry_price - $t->sl_price);
      if ($risk <= 0) return null;
      return ($t->exit_price - $t->entry_price) / $risk;
    }), 3) : 0;

    $netSum = round($trades->sum(fn($t) => (float)($t->net_profit ?? 0)), 2);
  @endphp

  <div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Trades (closed)</div><div class="fs-4 fw-bold">{{ $tradeCount }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Win rate</div><div class="fs-4 fw-bold">{{ $winRate }}%</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Avg R</div><div class="fs-4 fw-bold">{{ $avgR }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Net Profit (all)</div><div class="fs-4 fw-bold">${{ number_format($netSum, 2) }}</div></div></div></div>
  </div>

  <h5 class="mb-2">Trades in scope</h5>
  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Date</th><th>Ticker</th><th>Entry</th><th>SL</th><th>Exit</th><th>Status</th><th>Fees</th><th>Net</th>
        </tr>
      </thead>
      <tbody>
        @forelse($trades as $t)
          <tr>
            <td>{{ $t->date ?? '-' }}</td>
            <td>{{ $t->ticker ?? '-' }}</td>
            <td>{{ is_numeric($t->entry_price ?? null) ? number_format($t->entry_price,2) : '−' }}</td>
            <td>{{ is_numeric($t->sl_price ?? null) ? number_format($t->sl_price,2) : '−' }}</td>
            <td>{{ is_numeric($t->exit_price ?? null) ? number_format($t->exit_price,2) : '−' }}</td>
            <td>{{ $t->status ?? '-' }}</td>
            <td>{{ is_numeric($t->fees ?? null) ? '$'.number_format($t->fees,2) : '−' }}</td>
            <td>{{ is_numeric($t->net_profit ?? null) ? '$'.number_format($t->net_profit,2) : '−' }}</td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center text-muted">No trades</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
