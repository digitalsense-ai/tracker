@extends('layouts.app')
@section('content')
<h2 class="mb-3">System Status</h2>
<div class="row g-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Health</div>
      <div class="card-body">
        <ul class="list-group">
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Datafeed <span class="badge bg-{{ $health['datafeed']['status'] === 'OK' ? 'success' : 'secondary' }}">{{ $health['datafeed']['status'] }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Cron/Jobs <span class="badge bg-{{ $health['cron']['status'] === 'OK' ? 'success' : 'secondary' }}">{{ $health['cron']['status'] }}</span>
          </li>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            Broker <span class="badge bg-warning">NOT CONFIGURED</span>
          </li>
        </ul>
        <small class="text-muted d-block mt-2">Last run (NY): {{ $health['cron']['last_run_ny'] ?? '-' }}</small>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header">Active Strategy Config</div>
      <div class="card-body">
        <div class="row">
          <div class="col-6"><strong>Range (min)</strong></div><div class="col-6">{{ $strategy['range_minutes'] }}</div>
          <div class="col-6"><strong>Entry buffer %</strong></div><div class="col-6">{{ $strategy['entry_buffer_percent'] }}</div>
          <div class="col-6"><strong>Require retest</strong></div><div class="col-6">{{ $strategy['require_retest'] ? 'Yes' : 'No' }}</div>
          <div class="col-6"><strong>SL buffer %</strong></div><div class="col-6">{{ $strategy['sl_buffer_percent'] }}</div>
          <div class="col-6"><strong>TP levels (R)</strong></div><div class="col-6">{{ implode(', ', $strategy['tp_levels'] ?? []) }}</div>
          <div class="col-6"><strong>Trailing stop</strong></div><div class="col-6">{{ $strategy['enable_trailing_stop'] ? 'On' : 'Off' }}</div>
          <div class="col-6"><strong>Session</strong></div><div class="col-6">{{ $strategy['session_start'] }}–{{ $strategy['session_end'] }}</div>
          <div class="col-6"><strong>Position USD</strong></div><div class="col-6">{{ $strategy['position_usd'] }}</div>
          <div class="col-6"><strong>Fees %</strong></div><div class="col-6">{{ $strategy['fee_percent'] }}</div>
          <div class="col-6"><strong>Min fee</strong></div><div class="col-6">{{ $strategy['fee_min_per_order'] }}</div>
          <div class="col-6"><strong>Datafeed</strong></div><div class="col-6">{{ strtoupper($strategy['datafeed']) }}</div>
          <div class="col-6"><strong>Yahoo suffix order</strong></div><div class="col-6">{{ implode(', ', $strategy['yahoo_suffixes'] ?? []) }}</div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection