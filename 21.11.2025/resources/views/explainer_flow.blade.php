@extends('layouts.app')
@section('content')
<h2 class="mb-3">Explainer Flow</h2>
<form class="row g-2 mb-3" method="get">
  <div class="col-md-4"><label class="form-label">Ticker</label><input type="text" class="form-control" name="ticker" value="{{ $ticker }}" placeholder="e.g. AAPL"></div>
  <div class="col-md-4"><label class="form-label">Date</label><input type="date" class="form-control" name="date" value="{{ $date }}"></div>
  <div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100">Load</button></div>
</form>
@if($trade)
  <div class="mb-3"><div class="card"><div class="card-header">Trade Snapshot</div><div class="card-body">
    <div class="row">
      <div class="col-6"><strong>Date</strong></div><div class="col-6">{{ $trade->date }}</div>
      <div class="col-6"><strong>Ticker</strong></div><div class="col-6">{{ $trade->ticker }}</div>
      <div class="col-6"><strong>Entry</strong></div><div class="col-6">{{ $trade->entry_price }}</div>
      <div class="col-6"><strong>SL</strong></div><div class="col-6">{{ $trade->sl_price }}</div>
      <div class="col-6"><strong>TP1</strong></div><div class="col-6">{{ $trade->tp1 }}</div>
      <div class="col-6"><strong>TP2</strong></div><div class="col-6">{{ $trade->tp2 }}</div>
      <div class="col-6"><strong>Exit</strong></div><div class="col-6">{{ $trade->exit_price }}</div>
      <div class="col-6"><strong>Status</strong></div><div class="col-6">{{ $trade->status }}</div>
      <div class="col-6"><strong>Net Profit</strong></div><div class="col-6">{{ $trade->net_profit }}</div>
    </div></div></div></div>
  <div class="card"><div class="card-header">Flow</div><div class="card-body">
    <ol class="list-group list-group-numbered">
      @foreach($flow as $f)
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span><strong>{{ $f['step'] }}</strong><br><small class="text-muted">{{ $f['detail'] }}</small></span>
          <span class="badge bg-{{ ($f['status'] ?? '') === 'pass' ? 'success' : (in_array($f['status'], ['TP1','TP2','TP3']) ? 'primary' : 'secondary') }}">{{ is_scalar($f['status']) ? $f['status'] : '-' }}</span>
        </li>
      @endforeach
    </ol>
  </div></div>
@else
  <div class="alert alert-warning">No trade found. Try selecting a ticker and date then click Load.</div>
@endif
@endsection