@extends('layouts.app')
@section('title', $model->name)
@section('header_title', $model->name)

@section('content')
  <section class="grid" style="grid-template-columns:1fr;gap:16px">
    <div class="card" style="padding:16px">
      <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
          <div class="bold">{{ $model->name }}</div>
          <div class="small">Wallet: <span class="mono">{{ $model->wallet ?: '#' }}</span></div>
          <div class="small">Available Cash: $#</div>
        </div>
        <div>
          <div class="small">Total P&L</div>
          <div class="{{ ($model->return_pct ?? 0) >= 0 ? 'good' : 'bad' }} bold" style="font-size:18px">
            {{ ($model->return_pct >= 0 ? '+' : '') . number_format($model->return_pct ?? 0,2) }}%
          </div>
        </div>
        <div>
          <div class="small">Total Fees</div>
          <div class="bold">$#</div>
        </div>
        <div>
          <div class="small">Hold Times</div>
          <div class="small">Long: # • Short: # • Flat: #</div>
        </div>
      </div>
    </div>
  </section>

  <section class="card" style="margin-top:16px">
    <div class="card-header">
      <div class="bold">Active Positions</div>
      <div class="small">Total Unrealized P&L: <span class="good">+#</span></div>
    </div>
    <div class="card-body grid" style="grid-template-columns:repeat(3,1fr);gap:12px">
      @foreach(range(1,3) as $i)
        <div class="card" style="padding:12px">
          <div class="small">Entry Time: #</div>
          <div class="small">Entry Price: #</div>
          <div class="small">Side: # • Leverage: #</div>
          <div class="small">Quantity: # • Margin: #</div>
          <div class="small">Unrealized P&L: <span class="good">+#</span></div>
          <div style="margin-top:8px"><a class="tab" href="#">VIEW</a></div>
        </div>
      @endforeach
    </div>
  </section>

  {{--
  <section class="card" style="margin-top:16px">
    <div class="card-header"><div class="bold">Last 25 Trades</div></div>
    <div class="card-body">
      <table class="table">
        <thead>
          <tr>
            <th>Side</th><th>Coin</th><th>Entry Price</th><th>Exit Price</th>
            <th>Quantity</th><th>Holding Time</th><th>Notional (Entry)</th><th>Notional (Exit)</th>
            <th>Total Fees</th><th>Net P&L</th>
          </tr>
        </thead>
        <tbody>
          @foreach(range(1,8) as $i)
            <tr>
              <td>#</td><td>#</td><td>#</td><td>#</td><td>#</td><td>#</td>
              <td>#</td><td>#</td><td>#</td>
              <td class="{{ $i % 2 ? 'good' : 'bad' }}">{{ $i % 2 ? '+#' : '-#' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </section>
  --}}

  <section class="card" style="margin-top:16px">
      <div class="card-header"><div class="bold">Model Chat (decisions)</div></div>
      <div class="card-body">
        @forelse(($logs ?? []) as $log)
          <div class="card" style="padding:8px;margin-bottom:8px">
            <div class="small">{{ $log->created_at->format('Y-m-d H:i:s') }} — Action: <b>{{ $log->action ?? '#' }}</b></div>
            <pre style="white-space:pre-wrap;font-size:12px">{{ json_encode($log->payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
          </div>
        @empty
          <div class="small">No decisions yet.</div>
        @endforelse
      </div>
  </section>
@endsection
