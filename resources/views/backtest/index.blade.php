@extends('layouts.app')
@section('content')
<h1>Backtest</h1>

<table class="table table-striped">
  <thead>
    <tr>
      <th>Date</th>
      <th>Ticker</th>
      <th>Entry</th>
      <th>Exit</th>
      <th>SL</th>
      <th>Fees (bps)</th>
      <th>Qty</th>
      <th>Net P/L</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
  @forelse($trades ?? [] as $t)
    <tr>
      <td>{{ $t->date ?? '' }}</td>
      <td>{{ $t->ticker ?? '' }}</td>
      <td>{{ $t->entry_price ?? '' }}</td>
      <td>{{ $t->exit_price ?? '' }}</td>
      <td>{{ $t->sl_price ?? '' }}</td>
      <td>{{ $t->fees_bps ?? '' }}</td>
      <td>{{ $t->qty ?? '' }}</td>
      <td>{{ $t->net_profit ?? '' }}</td>
      <td>{{ $t->status ?? '' }}</td>
    </tr>
  @empty
    <tr><td colspan="9" class="text-muted">No trades found.</td></tr>
  @endforelse
  </tbody>
</table>
@endsection
