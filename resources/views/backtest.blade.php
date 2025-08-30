@php
    // $results: array of associative rows (ticker, side, entry_price, exit_price, sl_price, tp1, tp2, qty, fees_bps)
    $rows = $results ?? [];
    $count = count($rows);
    $wins = 0; $netpnl = 0.0; $rsum = 0.0; $rcnt = 0;

    foreach ($rows as &$r) {
        $side  = strtolower($r['side'] ?? 'long');
        $entry = (float)($r['entry_price'] ?? 0);
        $exit  = isset($r['exit_price']) ? (float)$r['exit_price'] : null;
        $sl    = isset($r['sl_price']) ? (float)$r['sl_price'] : null;
        $qty   = (float)($r['qty'] ?? 1);
        $fees_bps = (float)($r['fees_bps'] ?? 0.0);

        $raw   = $exit !== null ? ($side === 'long' ? $exit - $entry : $entry - $exit) : 0.0;
        $gross = $raw * $qty;
        $fee_mult = ($fees_bps/10000.0) * 2; // round-trip
        $fees = $qty * $entry * $fee_mult;
        $net  = $gross - $fees;
        $r['_pnl'] = $net;
        if ($net >= 0) $wins++;

        $ret = $exit !== null ? (($side==='long' ? ($exit/$entry-1) : ($entry/$exit-1)) * 100.0) : 0.0;
        $r['_ret'] = $ret;

        if ($sl !== null && $exit !== null && $entry != $sl) {
            $R = $side==='long' ? ($exit-$entry)/max(1e-9,($entry-$sl)) : ($entry-$exit)/max(1e-9,($sl-$entry));
            $r['_R'] = $R;
            $rsum += $R; $rcnt++;
        } else {
            $r['_R'] = null;
        }
        $netpnl += $net;
    }
    $winrate = $count ? round($wins/$count*100,1) : 0;
    $avgrr = $rcnt ? round($rsum/$rcnt,2) : null;
@endphp
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Backtest Results</title>
  <link rel="stylesheet" href="{{ asset('css/backtest.css') }}">
</head>
<body>
  <div class="container">
    <header class="header">
      <h1>Backtest Results</h1>
      <div class="toolbar">
        <div class="pill total">Trades: <strong>{{ $count }}</strong></div>
        <div class="pill win">Win rate: <strong>{{ $winrate }}%</strong></div>
        <div class="pill pnl">Net P&amp;L: <strong>{{ number_format($netpnl,2) }}</strong></div>
        <div class="pill rr">Avg R:R: <strong>{{ $avgrr !== null ? number_format($avgrr,2) : '—' }}</strong></div>
      </div>
    </header>

    <div class="table-wrap">
      <table id="results">
        <thead>
          <tr>
            <th>Date</th>
            <th>Ticker</th>
            <th>Side</th>
            <th>Entry</th>
            <th>Exit</th>
            <th>SL</th>
            <th>TP1</th>
            <th>TP2</th>
            <th>Qty</th>
            <th>Fees (bps)</th>
            <th>P/L</th>
            <th>Return %</th>
            <th>R</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $r)
            @php
              $outcome = ($r['_pnl'] ?? 0) >= 0 ? 'Win' : 'Loss';
              $side = strtolower($r['side'] ?? 'long');
            @endphp
            <tr class="{{ $outcome === 'Win' ? 'row-win' : 'row-loss' }}">
              <td>{{ $r['date'] ?? '—' }}</td>
              <td class="ticker">{{ $r['ticker'] ?? '-' }}</td>
              <td class="side {{ $side }}">{{ ucfirst($side) }}</td>
              <td>{{ isset($r['entry_price']) ? number_format($r['entry_price'],2) : '—' }}</td>
              <td>{{ isset($r['exit_price']) ? number_format($r['exit_price'],2) : '—' }}</td>
              <td>{{ isset($r['sl_price']) ? number_format($r['sl_price'],2) : '—' }}</td>
              <td>{{ isset($r['tp1']) ? number_format($r['tp1'],2) : '—' }}</td>
              <td>{{ isset($r['tp2']) ? number_format($r['tp2'],2) : '—' }}</td>
              <td>{{ $r['qty'] ?? 1 }}</td>
              <td>{{ isset($r['fees_bps']) ? rtrim(rtrim(number_format($r['fees_bps'],2),'0'),'.') : '0' }}</td>
              <td>{{ number_format($r['_pnl'] ?? 0, 2) }}</td>
              <td>{{ isset($r['_ret']) ? number_format($r['_ret'],2).'%' : '—' }}</td>
              <td>{{ $r['_R'] !== null ? number_format($r['_R'],2) : '—' }}</td>
              <td><span class="badge {{ strtolower($outcome) }}">{{ $outcome }}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
