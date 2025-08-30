<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Strategy Settings</title>
  <link rel="stylesheet" href="{{ asset('css/tracker-theme.css') }}">
  <style>
    .section{margin-bottom:24px;}
    .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    @media(max-width:900px){.grid{grid-template-columns:repeat(1,minmax(0,1fr))}}
    .card{padding:16px}
    label{display:block;font-size:13px;color:var(--muted);margin-bottom:6px}
    input,select{width:100%;padding:10px 12px;background:#0f1626;color:var(--text);border:1px solid var(--border);border-radius:10px}
    .checkbox{display:flex;align-items:center;gap:10px}
    .checkbox input{width:auto}
    .topbar{display:flex;align-items:center;justify-content:space-between;margin:18px 0}
    .btn{background:#1c2b49;color:var(--text);padding:10px 14px;border:1px solid var(--border);border-radius:10px;cursor:pointer}
    .btn:hover{filter:brightness(1.1)}
  </style>
</head>
<body class="tracker">
  <div class="container">
    <div class="topbar">
      <h1>Strategy Settings (ORB Retest v2.3)</h1>
      <form method="post" action="{{ route('settings.update') }}">
        @csrf

        @foreach($groups as $groupName => $rows)
          <div class="section">
            <h2 style="margin:0 0 10px 0;text-transform:capitalize">{{ $groupName }}</h2>
            <div class="card">
              <div class="grid">
                @foreach($rows as $r)
                  <div>
                    <label>{{ $r->label ?? $r->key }}</label>
                    @if($r->type === 'bool')
                      <div class="checkbox">
                        <input type="checkbox" name="settings[{{ $r->key }}][value]" {{ (int)($r->value ?? 0) ? 'checked' : '' }}>
                        <span>Enabled</span>
                      </div>
                    @elseif($r->type === 'time')
                      <input type="time" name="settings[{{ $r->key }}][value]" value="{{ $r->value }}">
                    @elseif(in_array($r->type, ['int','float','string']))
                      <input type="{{ $r->type === 'string' ? 'text' : 'number' }}"
                             step="{{ $r->type === 'int' ? '1' : ($r->type === 'float' ? '0.01' : '') }}"
                             name="settings[{{ $r->key }}][value]"
                             value="{{ $r->value }}">
                    @else
                      <input type="text" name="settings[{{ $r->key }}][value]" value="{{ $r->value }}">
                    @endif

                    <input type="hidden" name="settings[{{ $r->key }}][type]" value="{{ $r->type }}">
                    <input type="hidden" name="settings[{{ $r->key }}][group]" value="{{ $r->group }}">
                    <input type="hidden" name="settings[{{ $r->key }}][label]" value="{{ $r->label }}">
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endforeach

        <div style="margin-top:14px;text-align:right">
          <button class="btn" type="submit">Gem ændringer</button>
        </div>
      </form>
    </div>

    @if(session('ok'))
      <div class="card" style="padding:12px;border-left:4px solid var(--green);margin-top:10px">
        {{ session('ok') }}
      </div>
    @endif
  </div>
</body>
</html>
