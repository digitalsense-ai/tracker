@extends('layouts.app')
@section('content')
<h2 class="mb-3">KPIs</h2>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-3">
    <label class="form-label">From</label>
    <input type="date" class="form-control" name="from" value="{{ $from }}">
  </div>
  <div class="col-md-3">
    <label class="form-label">To</label>
    <input type="date" class="form-control" name="to" value="{{ $to }}">
  </div>
  <div class="col-md-3">
    <label class="form-label">Ticker</label>
    <input type="text" class="form-control" name="ticker" value="{{ $ticker }}" placeholder="e.g. AAPL">
  </div>
  <div class="col-md-3 d-flex align-items-end">
    <button class="btn btn-primary w-100">Apply</button>
  </div>
</form>
<div class="row g-3 mb-3">
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="fw-bold">Trades</div><div class="fs-4">{{ $count }}</div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="fw-bold">Win rate</div><div class="fs-4">{{ $winrate !== null ? $winrate.'%' : '-' }}</div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="fw-bold">Avg R</div><div class="fs-4">{{ $avgR ?? '-' }}</div></div></div></div>
  <div class="col-md-3"><div class="card"><div class="card-body"><div class="fw-bold">Net Profit</div><div class="fs-4">{{ number_format($sumNet, 2) }}</div></div></div></div>
</div>
<div class="table-responsive">
  <table class="table table-sm table-striped">
    <thead><tr><th>Date</th><th>Ticker</th><th>Entry</th><th>SL</th><th>Exit</th><th>Status</th><th>Net</th></tr></thead>
    <tbody>
      @foreach($rows as $r)
      <tr>
        <td>{{ $r->date }}</td>
        <td>{{ $r->ticker }}</td>
        <td>{{ $r->entry_price }}</td>
        <td>{{ $r->sl_price }}</td>
        <td>{{ $r->exit_price }}</td>
        <td>{{ $r->status }}</td>
        <td>{{ $r->net_profit }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection