@extends('layouts.app')
@section('title','Status')
@section('content')
<h1 class="mb-3">System Status</h1>

<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card p-3"><small>Datafeed</small><h2><span class="badge bg-success">OK</span></h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Cron / Jobs</small><h2><span class="badge bg-success">OK</span></h2></div></div>
  <div class="col-md-3"><div class="card p-3"><small>Broker</small><h2><span class="badge bg-warning text-dark">NOT CONFIGURED</span></h2></div></div>
</div>

<h5>Active Strategy Config</h5>
<table class="table table-sm">
  <tr><th>Opening Range (min)</th><td>{{ $range_min ?? 15 }}</td></tr>
  <tr><th>Require Retest</th><td>{{ ($require_retest ?? true) ? 'Yes':'No' }}</td></tr>
  <tr><th>Session</th><td>{{ ($session_start ?? '09:30') }}–{{ ($session_end ?? '16:00') }} (NY)</td></tr>
  <tr><th>Position</th><td>{{ $currency ?? 'kr' }} {{ number_format($position ?? 1000,0,',','.') }}</td></tr>
  <tr><th>Fees</th><td>{{ number_format(($fee_percent ?? 0.001)*100,3) }} % (min {{ $currency ?? 'kr' }} {{ number_format($fee_min ?? 2,2) }})</td></tr>
  <tr><th>Entry Buffer</th><td>{{ number_format($entry_buffer ?? 0.05,2) }} %</td></tr>
  <tr><th>SL Buffer</th><td>{{ number_format($sl_buffer ?? 0.05,2) }} %</td></tr>
</table>

<div class="alert alert-info small mt-3">
  Fee percent is entered as a fraction (e.g. 0.001 = 0.1%). Displayed here as %.
</div>
@endsection
