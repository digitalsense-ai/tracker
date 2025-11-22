<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Backtest Results</title>
  <link rel="stylesheet" href="{{ asset('css/tracker-theme.css') }}">
</head>
<body class="tracker">
  <div class="container">
    <div class="header">
      <h1>Backtest Results</h1>
    </div>
    <div class="card">
      <table>
        <thead>
          <tr><th>Date</th><th>Ticker</th><th>Status</th><th>Side</th><th>Entry</th><th>SL</th><th>Exit</th><th>Qty</th><th>Fees(bps)</th></tr>
        </thead>
        <tbody>
          @foreach($results as $r)
            <tr>
              <td>{{ $r['date'] ?? '-' }}</td>
              <td>{{ $r['ticker'] ?? '-' }}</td>
              <td>{{ $r['status'] ?? '-' }}</td>
              <td>{{ $r['side'] ?? '-' }}</td>
              <td>{{ isset($r['entry']) ? number_format($r['entry'],2) : '—' }}</td>
              <td>{{ isset($r['sl']) ? number_format($r['sl'],2) : '—' }}</td>
              <td>{{ isset($r['exit']) ? number_format($r['exit'],2) : '—' }}</td>
              <td>{{ number_format($r['qty'] ?? 1,0) }}</td>
              <td>{{ number_format($r['fees_bps'] ?? 0,2) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
