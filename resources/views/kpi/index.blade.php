@extends('layouts.app')
@section('content')
<h1>KPIs</h1>

@php
  $summary = $summary ?? [
    'closed' => $closed ?? 0,
    'winrate' => $winrate ?? 0,
    'avg_r' => $avg_r ?? 0,
    'net' => $net ?? 0,
  ];
  $rows = $rows ?? $items ?? [];
@endphp

<div class="card">
  <table class="table">
    <tbody>
      <tr><th>Trades (closed)</th><td>{{ $summary['closed'] }}</td></tr>
      <tr><th>Win rate</th><td>{{ $summary['winrate'] }}%</td></tr>
      <tr><th>Avg R</th><td>{{ $summary['avg_r'] }}</td></tr>
      <tr><th>Net Profit (all)</th><td>{{ $summary['net'] }}</td></tr>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Trades in scope</h2>
  <table class="table">
    <thead><tr><th>Date</th><th>Ticker</th><th>Entry</th><th>SL</th><th>Exit</th><th>Status</th><th>Fees</th><th>Net</th></tr></thead>
    <tbody>
      @forelse($rows as $t)
      <tr>
        <td>{{ $t->date ?? '' }}</td>
        <td>{{ $t->ticker ?? '' }}</td>
        <td>{{ $t->entry_price ?? '' }}</td>
        <td>{{ $t->sl_price ?? '' }}</td>
        <td>{{ $t->exit_price ?? '' }}</td>
        <td>{{ $t->status ?? '' }}</td>
        <td>{{ $t->fees ?? $t->fee ?? '' }}</td>
        <td>{{ $t->net_profit ?? $t->net ?? '' }}</td>
      </tr>
      @empty
      <tr><td colspan="8" class="text-muted">No trades.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
