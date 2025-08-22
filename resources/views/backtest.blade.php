@extends('layouts.app')
@section('title','Backtest')
@section('content')
<h1 class="mb-3">Backtest Results (ORB + Retest Strategy)</h1>

<form class="row g-2 mb-3" method="get">
  <div class="col-auto">
    <label class="form-label">From</label>
    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
  </div>
  <div class="col-auto">
    <label class="form-label">To</label>
    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
  </div>
  <div class="col-auto">
    <label class="form-label">Ticker</label>
    <input type="text" name="ticker" class="form-control" placeholder="AAPL" value="{{ request('ticker') }}">
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-primary">Run</button>
  </div>
</form>

<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card p-3"><small>Trades</small><h2>{{ $kpi['trades'] ?? 0 }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Win rate</small><h2>{{ number_format($kpi['win_rate'] ?? 0,1) }}%</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Avg R</small><h2>{{ number_format($kpi['avg_r'] ?? 0,2) }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Net</small>@php $n=$kpi['net'] ?? 0; @endphp <h2 class="{{ $n>=0?'money-pos':'money-neg' }}">{{ $currency ?? 'kr' }} {{ number_format($n,2) }}</h2></div></div>
</div>

<div class="table-responsive">
<table class="table table-striped table-sm align-middle">
  <thead>
    <tr>
      <th>Ticker</th><th>Entry</th><th>SL</th><th>TP1</th><th>TP2</th><th>Exit</th><th>Status</th><th>Reason</th>
    </tr>
  </thead>
  <tbody>
    @forelse(($rows ?? []) as $r)
      <tr>
        <td>{{ $r['ticker'] ?? '-' }}</td>
        <td>{{ number_format($r['entry'] ?? $r['entry_price'] ?? 0,2) }}</td>
        <td>{{ number_format($r['sl'] ?? $r['sl_price'] ?? 0,2) }}</td>
        <td>{{ isset($r['tp1']) ? number_format($r['tp1'],2) : '—' }}</td>
        <td>{{ isset($r['tp2']) ? number_format($r['tp2'],2) : '—' }}</td>
        {{--<td>{{ isset($r['exit'] ?? $r['exit_price']) ? number_format($r['exit'] ?? $r['exit_price'],2) : '—' }}</td>--}}
        <td>{{ isset($r['exit']) || isset($r['exit_price']) ? number_format($r['exit'] ?? $r['exit_price'], 2) : '—' }}</td>
        <td>
          @if(($r['status'] ?? '')==='SL') <span class="badge badge-sl">SL</span>
          @elseif(in_array(($r['status'] ?? ''),['TP1','TP2'])) <span class="badge badge-tp">{{ $r['status'] }}</span>
          @else <span class="badge bg-secondary">{{ $r['status'] ?? '-' }}</span>
          @endif
        </td>
        <td>{{ $r['reason'] ?? '-' }}</td>
      </tr>
    @empty
      <tr><td colspan="8" class="text-center text-muted">No simulated backtest rows.</td></tr>
    @endforelse
  </tbody>
</table>
</div>
@endsection
