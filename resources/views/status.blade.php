@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="mb-4">System Status</h1>

  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card border-success">
        <div class="card-body">
          <h5 class="card-title">Datafeed</h5>
          <span class="badge bg-success">OK</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-success">
        <div class="card-body">
          <h5 class="card-title">Cron/Jobs</h5>
          <span class="badge bg-success">OK</span>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card border-warning">
        <div class="card-body">
          <h5 class="card-title">Broker</h5>
          <span class="badge bg-warning text-dark">NOT CONFIGURED</span>
        </div>
      </div>
    </div>
  </div>

  <h4 class="mt-3">Active Strategy Config</h4>
  @php
    $cfg = $config ?? [
      'range_minutes' => env('STRATEGY_RANGE_MINUTES', 15),
      'require_retest' => env('STRATEGY_REQUIRE_RETEST', true),
      'session_start' => env('STRATEGY_SESSION_START', '09:30'),
      'session_end'   => env('STRATEGY_SESSION_END', '16:00'),
      'position_usd'  => env('STRATEGY_POSITION_USD', 1000),
      'fee_percent'   => env('STRATEGY_FEE_PERCENT', 0.001),
      'fee_min'       => env('STRATEGY_FEE_MIN_PER_ORDER', 2),
      'entry_buffer_percent' => env('STRATEGY_ENTRY_BUFFER_PERCENT', 0.0),
      'sl_buffer_percent'    => env('STRATEGY_SL_BUFFER_PERCENT', 0.0),
    ];
    $feePctDisplay = number_format($cfg['fee_percent'] * 100, 3) . ' %';
  @endphp

  <table class="table table-sm mt-2 w-auto">
    <tr><th>Opening Range (min)</th><td>{{ $cfg['range_minutes'] }}</td></tr>
    <tr><th>Require Retest</th><td>{{ $cfg['require_retest'] ? 'Yes' : 'No' }}</td></tr>
    <tr><th>Session</th><td>{{ $cfg['session_start'] }} - {{ $cfg['session_end'] }}</td></tr>
    <tr><th>Position (USD)</th><td>${{ number_format((float)$cfg['position_usd'], 2) }}</td></tr>
    <tr><th>Fees</th><td>{{ $feePctDisplay }} (min ${{ number_format((float)$cfg['fee_min'],2) }})</td></tr>
    <tr><th>Entry Buffer</th><td>{{ number_format((float)$cfg['entry_buffer_percent'], 3) }} %</td></tr>
    <tr><th>SL Buffer</th><td>{{ number_format((float)$cfg['sl_buffer_percent'], 3) }} %</td></tr>
  </table>

  <p class="text-muted">Note: Fee percent expects a <em>fraction</em> (e.g. 0.001 = 0.1%).</p>
</div>
@endsection
