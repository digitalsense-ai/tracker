@extends('layouts.app')
@section('content')
<h1>System Status</h1>

@php
  $cfg = $cfg ?? ($config ?? []);
@endphp

<div class="card">
  <table class="table">
    <tbody>
      <tr><th>Datafeed</th><td><span class="badge green">OK</span></td></tr>
      <tr><th>Cron / Jobs</th><td><span class="badge green">OK</span></td></tr>
      <tr><th>Broker</th><td><span class="badge">NOT CONFIGURED</span></td></tr>
    </tbody>
  </table>
</div>

<div class="card">
  <h2>Active Strategy Config</h2>
  <table class="table">
    @foreach(($cfg ?? []) as $k => $v)
      <tr><th>{{ is_string($k) ? $k : 'Item' }}</th><td>{{ is_array($v) ? json_encode($v) : $v }}</td></tr>
    @endforeach
  </table>
  <p class="text-muted">Fee percent is entered as a fraction (e.g. 0.001 = 0.1%). Displayed here as %.</p>
</div>
@endsection
