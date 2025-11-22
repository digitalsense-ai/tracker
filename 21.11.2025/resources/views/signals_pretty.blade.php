
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Signals</title>
  <link rel="stylesheet" href="{{ asset('css/tracker-theme.css') }}">
</head>
<body class="tracker">
  <div class="container">
    <div class="header">
      <h1>Signals</h1>
      <x-summary-bar :items="[
        ['label'=>'Status','value'=>$status ?? 'ok','type'=>($status ?? 'ok')==='ok'?'win':'warn'],
        ['label'=>'Count','value'=>isset($signals)?count($signals):0,'type'=>'info'],
      ]" />
    </div>
    <div class="card" style="padding:16px;white-space:pre-wrap;font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;">
      {!! $prettyJson !!}
    </div>
  </div>
</body>
</html>
