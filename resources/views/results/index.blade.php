@extends('layouts.app')
@section('content')
<h1>Simulated Trade Results</h1>

@php
  $rows = $rows ?? $rowsAll ?? $items ?? [];
  $summary = $summary ?? [
    'closed' => $closed ?? 0,
    'winrate' => $winrate ?? 0,
    'avg_r' => $avg_r ?? 0,
    'fees' => $fees_sum ?? 0,
    'net' => $net_sum ?? 0,
  ];
@endphp

<div class="card">
  <table class="table">
    <thead><tr><th>Closed trades</th><th>Win rate</th><th>Avg R</th><th>Fees (sum)</th><th>Net (sum)</th></tr></thead>
    <tbody><tr>
      <td>{{ $summary['closed'] }}</td>
      <td>{{ $summary['winrate'] }}%</td>
      <td>{{ $summary['avg_r'] }}</td>
      <td>{{ $summary['fees'] }}</td>
      <td>{{ $summary['net'] }}</td>
    </tr></tbody>
  </table>
</div>

<div class="card">
  <table class="table">
    <thead><tr>
      <th>Date</th><th>Ticker</th><th>Status</th><th>Entry</th><th>SL</th><th>Exit</th><th>Fees</th><th>Net</th>
    </tr></thead>
    <tbody>
      @forelse($rows as $t)
        <tr>
          <td>{{ $t->date ?? '' }}</td>
          <td>{{ $t->ticker ?? '' }}</td>
          <td><span class="badge {{ ($t->status ?? '') === 'SL' ? 'red' : 'blue' }}">{{ $t->status ?? '' }}</span></td>
          <td>{{ $t->entry_price ?? '' }}</td>
          <td>{{ $t->sl_price ?? '' }}</td>
          <td>{{ $t->exit_price ?? '' }}</td>
          <td>{{ $t->fees ?? '' }}</td>
          <td>{{ $t->net_profit ?? $t->net ?? '' }}</td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-muted">No results.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
