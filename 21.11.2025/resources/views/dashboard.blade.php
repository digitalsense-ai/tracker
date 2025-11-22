@extends('layouts.app')
@section('title','Dashboard')
@section('content')
<h1 class="mb-3">Dashboard</h1>
<p class="text-muted">Window: {{ $from ?? '' }} → {{ $to ?? '' }} (approx. last 5 trading days)</p>

@php $cur = $currency ?? 'kr'; @endphp
<div class="row g-3">
  <div class="col-md-3"><div class="card p-3"><small>Trades today (closed)</small><h2>{{ $todayTrades ?? 0 }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Open now</small><h2>{{ $openTrades ?? 0 }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Win rate (5d)</small><h2>{{ number_format($winRate5d ?? 0,1) }}%</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Avg R (5d)</small><h2>{{ number_format($avgR5d ?? 0,2) }}</h2></div></div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-3"><div class="card p-3"><small>Net (5d)</small><h2 class="{{ ($net5d ?? 0)>=0 ? 'money-pos':'money-neg' }}">{{ $cur }} {{ number_format($net5d ?? 0,2) }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Fees (5d)</small><h2>{{ $cur }} {{ number_format($fees5d ?? 0,2) }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Profit factor (5d)</small><h2>{{ is_infinite($profitFactor5d ?? 0) ? '∞' : number_format($profitFactor5d ?? 0,2) }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>TP / SL hit (5d)</small><h2>{{ number_format($tpRate5d ?? 0,1) }}% / {{ number_format($slRate5d ?? 0,1) }}%</h2></div></div>
</div>

<h5 class="mt-4">Best / Worst (5d)</h5>
<ul>
  <li>Best: {{ $best->ticker ?? '-' }} {{ $cur }} {{ number_format($best->net_profit ?? 0,2) }}</li>
  <li>Worst: {{ $worst->ticker ?? '-' }} {{ $cur }} {{ number_format($worst->net_profit ?? 0,2) }}</li>
</ul>

<h5 class="mt-4">Recent 10 trades</h5>
<div class="table-responsive">
<table class="table table-sm table-striped">
  <thead><tr><th>Date</th><th>Ticker</th><th>Status</th><th>Entry</th><th>SL</th><th>Exit</th><th>Net</th></tr></thead>
  <tbody>
  @forelse(($recent ?? []) as $t)
    <tr>
      <td>{{ $t->date ?? '-' }}</td>
      <td>{{ $t->ticker ?? '-' }}</td>
      <td>
        @if(($t->status ?? '')==='SL') <span class="badge badge-sl">SL</span>
        @elseif(in_array(($t->status ?? ''),['TP1','TP2'])) <span class="badge badge-tp">{{ $t->status }}</span>
        @else <span class="badge bg-secondary">{{ $t->status ?? '-' }}</span>
        @endif
      </td>
      <td>{{ is_numeric($t->entry_price ?? null) ? number_format($t->entry_price,2) : '—' }}</td>
      <td>{{ is_numeric($t->sl_price ?? null) ? number_format($t->sl_price,2) : '—' }}</td>
      <td>{{ is_numeric($t->exit_price ?? null) ? number_format($t->exit_price,2) : '—' }}</td>
      <td class="{{ ($t->net_profit ?? 0)>=0 ? 'money-pos':'money-neg' }}">{{ $cur }} {{ number_format($t->net_profit ?? 0,2) }}</td>
    </tr>
  @empty
    <tr><td colspan="7" class="text-muted">No trades.</td></tr>
  @endforelse
  </tbody>
</table>
</div>

@if(($perTicker ?? null) && count($perTicker))
<h5 class="mt-4">By Ticker (5d)</h5>
<div class="row g-3">
@foreach($perTicker as $sym => $m)
  <div class="col-md-4">
    <div class="card p-3">
      <div class="d-flex justify-content-between">
        <strong>{{ $sym }}</strong>
        <span class="{{ ($m['net'] ?? 0)>=0 ? 'money-pos':'money-neg' }}">{{ $cur }} {{ number_format($m['net'] ?? 0,2) }}</span>
      </div>
      <small class="text-muted">{{ $m['trades'] ?? 0 }} trades · Win {{ number_format($m['winRate'] ?? 0,1) }}%</small>
    </div>
  </div>
@endforeach
</div>
@endif

<div class="mt-4 d-flex gap-2">
  <a class="btn btn-outline-primary btn-sm" href="/kpi">Open KPI</a>
  <a class="btn btn-outline-secondary btn-sm" href="/results">Open Results</a>
  <a class="btn btn-outline-dark btn-sm" href="/explainer-flow">Open Explainer Flow</a>
</div>
@endsection
