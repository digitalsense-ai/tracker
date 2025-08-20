@extends('layouts.app')

@section('content')
<div class="container mt-4">
  <h1 class="mb-3">Backtest Results (ORB + Retest Strategy)</h1>

  @php
    if (!function_exists('bf_field')) {
        function bf_field($row, $names) {
            foreach ($names as $n) {
                if (is_array($row) && array_key_exists($n, $row)) return $row[$n];
                if (is_object($row) && isset($row->$n)) return $row->$n;
            }
            return null;
        }
    }
    if (!function_exists('bf_num')) {
        function bf_num($val, $dec = 2) {
            return is_numeric($val) ? number_format((float)$val, $dec) : '−';
        }
    }
  @endphp

  <div class="table-responsive">
    <table class="table table-striped table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>Ticker</th>
          <th>Entry</th>
          <th>SL</th>
          <th>TP1</th>
          <th>TP2</th>
          <th>Exit</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
      @forelse ($results as $r)
        @php
          $ticker = bf_field($r, ['ticker','symbol']);
          $entry  = bf_field($r, ['entry','entryPrice']);
          $sl     = bf_field($r, ['sl','slPrice']);
          $tp1    = bf_field($r, ['tp1','tp1Price']);
          $tp2    = bf_field($r, ['tp2','tp2Price']);
          $exit   = bf_field($r, ['exit','exitPrice']);
          $status = bf_field($r, ['status','state']);
        @endphp
        <tr>
          <td>{{ $ticker }}</td>
          <td>{{ bf_num($entry) }}</td>
          <td>{{ bf_num($sl) }}</td>
          <td>{{ bf_num($tp1) }}</td>
          <td>{{ bf_num($tp2) }}</td>
          <td>{{ bf_num($exit) }}</td>
          <td>{{ $status ?? '−' }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-center text-muted">No simulated backtest rows.</td>
        </tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
