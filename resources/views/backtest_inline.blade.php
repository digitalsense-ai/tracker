<?php /* resources/views/backtest_inline.blade.php */ ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Backtest Results</title>
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial;margin:20px}
    table{border-collapse:collapse;width:100%;max-width:900px}
    th,td{border:1px solid #ddd;padding:8px}
    th{background:#f5f7fb;font-weight:600}
    .win{color:#0a8f49;font-weight:700}
    .loss{color:#d33;font-weight:700}
  </style>
</head>
<body>
  <h1>Backtest Results</h1>
  <table>
    <thead><tr><th>Date</th><th>Ticker</th><th>Status</th><th>Side</th><th>Entry</th><th>SL</th><th>Exit</th><th>Qty</th><th>Fees(bps)</th></tr></thead>
    <tbody>
    <?php foreach(($results ?? []) as $r): ?>
      <tr>
        <td><?= htmlspecialchars($r['date'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['ticker'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['status'] ?? '-') ?></td>
        <td><?= htmlspecialchars($r['side'] ?? '-') ?></td>
        <td><?= number_format($r['entry'] ?? 0, 2) ?></td>
        <td><?= isset($r['sl']) ? number_format($r['sl'], 2) : '—' ?></td>
        <td><?= isset($r['exit']) ? number_format($r['exit'], 2) : '—' ?></td>
        <td><?= number_format($r['qty'] ?? 1, 0) ?></td>
        <td><?= number_format($r['fees_bps'] ?? 0, 2) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
