<!DOCTYPE html>
<html>
<head><title>Backtest Results</title></head>
<body>
<h1>Backtest Results</h1>
<table border="1">
    <tr>
        <th>Ticker</th><th>Entry</th><th>Exit</th><th>SL</th><th>TP1</th><th>TP2</th>
    </tr>
    @foreach ($results as $r)
    <tr>
        <td>{{ $r['ticker'] }}</td>
        <td>{{ number_format($r['entry_price'], 2) }}</td>
        <td>{{ $r['exit_price'] ? number_format($r['exit_price'], 2) : '—' }}</td>
        <td>{{ $r['sl_price'] ? number_format($r['sl_price'], 2) : '—' }}</td>
        <td>{{ $r['tp1'] ? number_format($r['tp1'], 2) : '—' }}</td>
        <td>{{ $r['tp2'] ? number_format($r['tp2'], 2) : '—' }}</td>
    </tr>
    @endforeach
</table>
</body>
</html>
