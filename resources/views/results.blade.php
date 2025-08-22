@extends('layouts.app')
@section('title','Results')
@section('content')
<h1 class="mb-3">Simulated Trade Results</h1>

@php $cur = $currency ?? 'kr'; @endphp

<form class="row g-2 mb-3" method="get">
  <div class="col-auto">
    <label class="form-label">Date</label>
    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
  </div>
  <div class="col-auto">
    <label class="form-label">Ticker</label>
    <input type="text" name="ticker" class="form-control" value="{{ request('ticker') }}" placeholder="AAPL">
  </div>
  <div class="col-auto">
    <label class="form-label">Status</label>
    <select name="status" class="form-select">
      <option value="">All</option>
      @foreach(['TP2','TP1','SL','closed','open'] as $st)
        <option value="{{ $st }}" @selected(request('status')===$st)>{{ $st }}</option>
      @endforeach
    </select>
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-primary">Filter</button>
    <a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export'=>'csv']) }}">Export CSV</a>
  </div>
</form>

<div class="row g-3 mb-3">
  <div class="col-md-2"><div class="card p-3"><small>Closed trades</small><h2>{{ $count ?? 0 }}</h2></div></div>
  <div class="col-md-2"><div class="card p-3"><small>Win rate</small><h2>{{ number_format($winRate ?? 0,1) }}%</h2></div></div>
  <div class="col-md-2"><div class="card p-3"><small>Avg R</small><h2>{{ number_format($avgR ?? 0,2) }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Fees (sum)</small><h2>{{ $cur }} {{ number_format($fees ?? 0,2) }}</h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Net (sum)</small>@php $n=$net ?? 0; @endphp <h2 class="{{ $n>=0?'money-pos':'money-neg' }}">{{ $cur }} {{ number_format($n,2) }}</h2></div></div>
</div>

<div class="table-responsive">
<table class="table table-striped table-bordered table-sm align-middle">
  <thead>
    <tr>
      <th>Date</th><th>Ticker</th><th>Status</th><th>Entry</th><th>SL</th><th>Exit</th><th>Fees</th><th>Net</th><th>🔍</th>
    </tr>
  </thead>
  <tbody>
  @forelse(($trades ?? []) as $t)
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
      <td>{{ $cur }} {{ number_format($t->fees ?? 0,2) }}</td>
      <td class="{{ ($t->net_profit ?? 0)>=0 ? 'money-pos':'money-neg' }}">{{ $cur }} {{ number_format($t->net_profit ?? 0,2) }}</td>
      <td><a class="btn btn-sm btn-outline-secondary" href="/explainer-flow?ticker={{ $t->ticker }}&date={{ $t->date }}">Open</a></td>
    </tr>
  @empty
    <tr><td colspan="9" class="text-muted text-center">No results.</td></tr>
  @endforelse
  </tbody>
</table>
</div>

{{ $trades->links() ?? '' }}
@endsection
