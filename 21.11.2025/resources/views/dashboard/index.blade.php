@extends('layouts.app')
@section('content')
<h1>Dashboard</h1>

@php
  $metrics = $metrics ?? [
    'trades_today' => $trades_today ?? 0,
    'open_now' => $open_now ?? 0,
    'winrate_5d' => $winrate_5d ?? 0,
    'avg_r_5d' => $avg_r_5d ?? 0,
    'net_5d' => $net_5d ?? 0,
    'fees_5d' => $fees_5d ?? 0,
    'pf_5d' => $pf_5d ?? 0,
    'tp_sl_5d' => $tp_sl_5d ?? '0% / 0%',
  ];
  $recent = $recent ?? $trades ?? [];
@endphp

<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>Trades today (closed)</th><th>Open now</th><th>Win rate (5d)</th><th>Avg R (5d)</th><th>Net (5d)</th><th>Fees (5d)</th><th>Profit factor (5d)</th><th>TP/SL hit (5d)</th></tr></thead>
      <tbody>
        <tr>
          <td>{{ $metrics['trades_today'] }}</td>
          <td>{{ $metrics['open_now'] }}</td>
          <td>{{ $metrics['winrate_5d'] }}%</td>
          <td>{{ $metrics['avg_r_5d'] }}</td>
          <td>{{ $metrics['net_5d'] }}</td>
          <td>{{ $metrics['fees_5d'] }}</td>
          <td>{{ $metrics['pf_5d'] }}</td>
          <td>{{ $metrics['tp_sl_5d'] }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h2>Recent 10 trades</h2>
  <table class="table">
    <thead><tr><th>Date</th><th>Ticker</th><th>Status</th><th>Entry</th><th>SL</th><th>Exit</th><th>Net</th></tr></thead>
    <tbody>
    @forelse($recent as $t)
      <tr>
        <td>{{ $t->date ?? '' }}</td>
        <td>{{ $t->ticker ?? '' }}</td>
        <td><span class="badge {{ ($t->status ?? '') === 'SL' ? 'red' : 'blue' }}">{{ $t->status ?? '' }}</span></td>
        <td>{{ $t->entry_price ?? $t->entry ?? '' }}</td>
        <td>{{ $t->sl_price ?? $t->sl ?? '' }}</td>
        <td>{{ $t->exit_price ?? $t->exit ?? '' }}</td>
        <td>{{ $t->net_profit ?? $t->net ?? '' }}</td>
      </tr>
    @empty
      <tr><td colspan="7" class="text-muted">No trades.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
