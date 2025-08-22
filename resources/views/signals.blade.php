@extends('layouts.app')
@section('title','Signals')
@section('content')
<h1 class="mb-3">Signals (Multi‑step ORB + Retest)</h1>
<form class="row g-2 mb-3" method="get">
  <div class="col-auto">
    <label class="form-label">Date</label>
    <input type="date" class="form-control" name="date" value="{{ $date ?? '' }}">
  </div>
  <div class="col-auto align-self-end">
    <button class="btn btn-primary">Run</button>
  </div>
</form>
<div class="table-responsive">
  <table class="table table-striped table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th>Ticker</th><th>Data</th><th>ORB</th><th>Breakout</th><th>Retest</th><th>Confirm</th><th>Entry</th><th>SL</th><th>TP1</th><th>TP2</th><th>Exit</th><th>Outcome</th><th>Net</th>
      </tr>
    </thead>
    <tbody>
      @forelse(($rows ?? []) as $r)
        <tr>
          <td>{{ $r['ticker'] ?? '-' }}</td>
          <td>{!! !empty($r['data_ok']) ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">NO</span>' !!}</td>
          <td>{!! !empty($r['orb_ok']) ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-danger">NO</span>' !!}</td>
          <td>{!! !empty($r['breakout']) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
          <td>{!! !empty($r['retest']) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
          <td>{!! !empty($r['confirm']) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' !!}</td>
          <td>{{ isset($r['entry']) ? number_format($r['entry'],2) : (isset($r['entry_price']) ? number_format($r['entry_price'],2) : '—') }}</td>
          <td>{{ isset($r['sl']) ? number_format($r['sl'],2) : (isset($r['sl_price']) ? number_format($r['sl_price'],2) : '—') }}</td>
          <td>{{ isset($r['tp1']) ? number_format($r['tp1'],2) : '—' }}</td>
          <td>{{ isset($r['tp2']) ? number_format($r['tp2'],2) : '—' }}</td>
          <td>{{ isset($r['exit']) ? number_format($r['exit'],2) : (isset($r['exit_price']) ? number_format($r['exit_price'],2) : '—') }}</td>
          <td>{{ $r['outcome'] ?? '-' }}</td>
          <td>{{ isset($r['net']) ? number_format($r['net'],2) : (isset($r['net_profit']) ? number_format($r['net_profit'],2) : '—') }}</td>
        </tr>
      @empty
        <tr><td colspan="13" class="text-center text-muted">No signals.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
