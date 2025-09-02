<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Signals (Pretty)</title>
  <link rel="stylesheet" href="{{ asset('css/tracker-theme.css') }}">
  <style>
    body{margin:20px}
    .toolbar{display:flex;gap:8px;margin-bottom:12px}
    table{width:100%;border-collapse:collapse}
    th,td{padding:8px;border-bottom:1px solid #e5e7eb}
    th{background:#f8fafc;text-align:left}
    .muted{color:#64748b}
  </style>
</head>
<body class="tracker">
  <div class="container">
    <h1>Signals</h1>
    <div class="toolbar">
      <button id="btnReload">Reload</button>
      <label class="muted"><input type="checkbox" id="cbRaw"> Show raw JSON</label>
    </div>
    <pre id="raw" style="display:none; white-space:pre-wrap; background:#f8fafc; padding:12px; border:1px solid #e5e7eb; border-radius:8px"></pre>
    <div class="card">
      <table id="tbl">
        <thead><tr>
          <th>Date</th><th>Ticker</th><th>Status</th><th>Entry</th><th>SL</th><th>Exit</th><th>Net</th>
        </tr></thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
<script>
async function load(){
  const res = await fetch('/signals');
  const text = await res.text();
  // tolerate both "pretty" and compact JSON
  const json = JSON.parse(text.replace(/^Flot udskrift\s*/i,''));
  const list = json.signals || [];
  document.getElementById('raw').textContent = JSON.stringify(json, null, 2);
  const tbody = document.querySelector('#tbl tbody');
  tbody.innerHTML='';
  for(const s of list){
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${s.date ?? ''}</td>
                    <td>${s.ticker ?? ''}</td>
                    <td>${s.status ?? ''}</td>
                    <td>${(s.entry_price??'') || ''}</td>
                    <td>${(s.sl_price??'') || ''}</td>
                    <td>${(s.exit_price??'') || ''}</td>
                    <td data-pnl>${(s.net_profit??s.net??'') || ''}</td>`;
    tbody.appendChild(tr);
  }
}
document.getElementById('btnReload').onclick = load;
document.getElementById('cbRaw').onchange = (e)=>{
  document.getElementById('raw').style.display = e.target.checked ? 'block' : 'none';
};
load();
</script>
</body>
</html>
