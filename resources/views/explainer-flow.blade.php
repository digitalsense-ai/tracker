@extends('layouts.app')
@section('title','Explainer Flow')
@section('content')
<h1 class="mb-3">Explainer Flow</h1>
<p class="text-muted">Step-by-step explanation for an ORB + Retest trade (Data → ORB → Breakout → Retest → Confirm → Exit).</p>

<form class="row g-2 mb-3" method="get">
  <div class="col-auto">
    <label class="form-label">Ticker</label>
    <input type="text" name="ticker" class="form-control" value="{{ request('ticker') }}" placeholder="AAPL">
  </div>
  <div class="col-auto">
    <label class="form-label">Date</label>
    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-primary">Open</button>
  </div>
</form>

<div class="alert alert-info">
  Link a trade from <a href="/results">Results</a> (🔍) to populate this view with a real example.
</div>
@endsection
