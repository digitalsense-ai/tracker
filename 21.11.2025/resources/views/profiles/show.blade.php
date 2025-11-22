@extends('layouts.app')
@section('title',"Profile — " . ($slug ?? '#'))
@section('header_title',"Profile — " . ($slug ?? '#'))

@section('content')
  <section class="card">
    <div class="card-header">
      <div class="bold">Overview</div>
      <div class="small">Return <span class="good">+#%</span> • Equity $#</div>
    </div>
    <div class="card-body">
      <div class="grid grid-3">
        <div class="card">
          <div class="card-header"><div class="bold">Equity Curve</div></div>
          <div class="card-body">
            <svg class="spark" viewBox="0 0 100 32" fill="none">
              <path d="M2 28 L20 20 L35 22 L52 12 L70 8 L98 10" stroke="#2563eb" stroke-width="2" fill="none" />
            </svg>
          </div>
        </div>
        <div class="card">
          <div class="card-header"><div class="bold">Stats</div></div>
          <div class="card-body">
            <div class="grid" style="grid-template-columns:repeat(2,1fr);gap:8px">
              <div class="card" style="padding:8px"><div class="small">Trades</div><div class="bold">#</div></div>
              <div class="card" style="padding:8px"><div class="small">Win Rate</div><div class="bold">#%</div></div>
              <div class="card" style="padding:8px"><div class="small">PF</div><div class="bold">#</div></div>
              <div class="card" style="padding:8px"><div class="small">DD</div><div class="bold">#%</div></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="card" style="margin-top:16px">
    <div class="card-header"><div class="bold">Model Chat</div></div>
    <div class="card-body">
      <div class="card" style="padding:8px">
        <div class="small">#</div>
        <div>#</div>
      </div>
    </div>
  </section>

  <section class="card" style="margin-top:16px">
    <div class="card-header"><div class="bold">Completed Trades</div></div>
    <div class="card-body">
      <table class="table">
        <thead><tr><th>Asset</th><th>Entry</th><th>Exit</th><th>PnL</th><th>Hold</th></tr></thead>
        <tbody>
          <tr><td>#</td><td>#</td><td>#</td><td class="good">+#</td><td>#</td></tr>
        </tbody>
      </table>
    </div>
  </section>
@endsection
