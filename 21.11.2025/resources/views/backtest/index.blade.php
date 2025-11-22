@extends('layouts.app')
@section('content')
<h1>Backtest Results</h1>

@php
  $rows = $rows ?? $trades ?? $items ?? [];
@endphp

<div class="card">
  <table class="table">
    <thead>
      <tr><th>Date</th><th>Ticker</th><th>Status</th><th>Side</th><th>Entry</th><th>SL</th><th>Exit</th><th>Qty</th><th>Fees(bps)</th></tr>
    </thead>
    <tbody>
      @forelse($rows as $t)
        <tr>
          <td>{{ $t->date ?? '' }}</td>
          <td>{{ $t->ticker ?? '' }}</td>
          <td>{{ $t->status ?? '' }}</td>
          <td>{{ $t->side ?? 'long' }}</td>
          <td>{{ $t->entry_price ?? $t->entry ?? '' }}</td>
          <td>{{ $t->sl_price ?? $t->sl ?? '' }}</td>
          <td>{{ $t->exit_price ?? $t->exit ?? '' }}</td>
          <td>{{ $t->qty ?? '' }}</td>
          <td>{{ $t->fees_bps ?? $t->feesbps ?? '' }}</td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-muted">No backtest rows.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
