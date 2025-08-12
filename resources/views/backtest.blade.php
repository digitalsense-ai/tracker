@extends('layouts.app')

@section('content')
<div class="container">
  <h2>Backtest Results (ORB + Retest Strategy)</h2>

  @if (!empty($summary))
    <div class="mb-3">
      <strong>Total:</strong> {{ $summary['total'] }}
      &nbsp;•&nbsp; <strong>Wins:</strong> {{ $summary['wins'] }}
      &nbsp;•&nbsp; <strong>Win rate:</strong> {{ $summary['winrate'] }}%
      &nbsp;•&nbsp; <strong>TP2:</strong> {{ $summary['tp2'] }}
      &nbsp;•&nbsp; <strong>SL:</strong> {{ $summary['sl'] }}
    </div>
  @endif

  @if (isset($results) && count($results) > 0)
  <table class="table table-sm table-bordered">
    <thead>
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
      @foreach ($results as $r)
        <tr>
          <td>{{ $r['ticker'] }}</td>
          <td>{{ number_format($r['entryPrice'], 2) }}</td>
          <td>{{ number_format($r['slPrice'], 2) }}</td>
          <td>{{ number_format($r['tp1'], 2) }}</td>
          <td>{{ number_format($r['tp2'], 2) }}</td>
          <td>{{ isset($r['exitPrice']) ? number_format($r['exitPrice'], 2) : '-' }}</td>
          <td>{{ $r['status'] }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  @else
    <p>No simulated trades generated.</p>
  @endif
</div>
@endsection
