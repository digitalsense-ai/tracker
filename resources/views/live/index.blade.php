@extends('layouts.app')
@section('title','Alpha Arena — Live')
@section('header_title','Alpha Arena — Live')

@section('content')
  <div class="grid grid-3">
    <section class="card">
      <div class="card-header">
        <div class="bold">Total Account Value</div>
        <div class="small">Current <b>$#</b></div>
      </div>
      <div class="card-body">
        <svg class="spark" viewBox="0 0 100 28" fill="none">
          <path d="M2 26 L20 20 L35 22 L52 12 L70 8 L98 10" stroke="#2563eb" stroke-width="2" fill="none" />
        </svg>
        <div class="small">ALL • $ / % toggles (#)</div>
      </div>
    </section>

    <aside class="card">
      <div class="card-header">
        <div class="bold">Panel</div>
        <div class="small">Positions • Completed Trades • Model Chat • README</div>
      </div>
      <div class="card-body" id="right-tabs">
        <div class="tabs" style="margin-bottom:10px;">
          <button class="tab active" data-tab="positions">Positions</button>
          <button class="tab" data-tab="trades">Completed Trades</button>
          <button class="tab" data-tab="chat">Model Chat</button>
          <button class="tab" data-tab="readme">README.txt</button>
        </div>

        <div data-panel="positions">
          <table class="table">
            <thead><tr><th>Ticker</th><th>Side</th><th>Notional</th><th>Unreal. PnL</th><th>Exit Plan</th></tr></thead>
            <tbody>
              <tr><td>#</td><td>#</td><td>$#</td><td class="good">+#</td><td><a href="#">VIEW</a></td></tr>
              <tr><td>#</td><td>#</td><td>$#</td><td class="bad">-#</td><td><a href="#">VIEW</a></td></tr>
            </tbody>
          </table>
        </div>

        <div data-panel="trades" hidden>
          <div class="small">Latest closed trades:</div>
          <div class="card" style="margin-top:8px;padding:8px">
            <div class="bold">#</div>
            <div class="small">Entry # • Exit # • PnL +# • Holding #H #M</div>
          </div>
        </div>

        <div data-panel="chat" hidden>
          <div class="card" style="padding:8px">
            <div class="small">10/26 18:50</div>
            <div>Holding all positions; no invalidation hit. Trend intact across #.</div>
          </div>
          <div class="card" style="margin-top:8px;padding:8px">
            <div class="small">10/26 08:00</div>
            <div>Stops and targets still valid. Volatility moderate (ATR%).</div>
          </div>
        </div>

        <div data-panel="readme" hidden>
          <p>This panel can contain system prompts, setup notes, or explanations of the active trading model. Replace all # with live content.</p>
        </div>
      </div>
    </aside>
  </div>

  <section class="card" style="margin-top:16px">
    <div class="card-header">
      <div class="bold">Leading Models</div>
      <div class="small">(mock)</div>
    </div>
    <div class="card-body leader-grid">
      @foreach (['DeepSeek V3.1','Qwen3 Max','Claude Sonnet 4.5','Grok 4','Gemini 2.5 Pro','GPT-5'] as $name)
        <div class="leader-item">
          <div class="bold">{{ $name }}</div>
          <div class="small">$#</div>
          <div class="{{ in_array($name,['Gemini 2.5 Pro','GPT-5']) ? 'bad' : 'good' }} bold">
            {{ in_array($name,['Gemini 2.5 Pro','GPT-5']) ? '-' : '+' }}#%
          </div>
        </div>
      @endforeach
    </div>
  </section>
@endsection
