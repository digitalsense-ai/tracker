@php use Illuminate\Support\Str; @endphp
<link rel="stylesheet" href="/css/ai.css">

<div class="card">
  <h3>Model: {{ $m->name }} <span class="badge">{{ $m->active ? 'ACTIVE' : 'PAUSED' }}</span></h3>
  <div class="kv">
    <div>Wallet / Account</div><div class="mono">{{ $m->wallet ?? '—' }}</div>
    <div>Equity</div><div>${{ number_format((float)$m->equity, 2) }}</div>
    <div>Goal</div><div>{{ $m->goal_label ?? '–' }}</div>
    <div>Check Interval</div><div>{{ $m->check_interval_min }} min</div>
    <div>Risk % (per trade)</div><div>{{ number_format((float)$m->risk_pct,2) }}%</div>
    <div>Peak Equity</div><div>${{ number_format((float)($m->peak_equity ?? 0), 2) }}</div>
  </div>
</div>

<div class="card">
  <h3>Guard Settings</h3>
  <div class="chips">
    <x-chip>Max Concurrent: {{ $m->max_concurrent_trades ?? 5 }}</x-chip>
    <x-chip>Same Symbol Re-Entry: {{ ($m->allow_same_symbol_reentry ?? false) ? 'Yes' : 'No' }}</x-chip>
    <x-chip>Cooldown: {{ $m->cooldown_minutes ?? 0 }} min</x-chip>
    <x-chip>Per Trade Alloc: {{ number_format((float)($m->per_trade_alloc_pct ?? 20),2) }}%</x-chip>
    <x-chip>Max Exposure: {{ number_format((float)($m->max_exposure_pct ?? 80),2) }}%</x-chip>
    <x-chip>Max Leverage: {{ number_format((float)($m->max_leverage ?? 5),2) }}x</x-chip>
    <x-chip>Max Drawdown: {{ number_format((float)($m->max_drawdown_pct ?? 0),2) }}%</x-chip>
  </div>
  <div class="small text-dim">These guardrails are enforced server‑side before any AI order executes.</div>
</div>

<div class="card">
  <h3>Active Positions ({{ $positions->count() }})</h3>
  <table class="table">
    <thead><tr>
      <th>Ticker</th><th>Side</th><th>Qty</th><th>Avg Price</th><th>Stop</th><th>Target</th><th>Unrealized PnL</th><th>Opened</th>
    </tr></thead>
    <tbody>
      @forelse ($positions as $p)
        <tr>
          <td class="mono">{{ $p->ticker }}</td>
          <td>{{ strtoupper($p->side) }}</td>
          <td>{{ (float)$p->qty }}</td>
          <td>${{ number_format((float)$p->avg_price, 2) }}</td>
          <td class="mono">{{ $p->stop_price ? '$'.number_format((float)$p->stop_price,2) : '—' }}</td>
          <td class="mono">{{ $p->target_price ? '$'.number_format((float)$p->target_price,2) : '—' }}</td>
          <td class="mono">{{ number_format((float)$p->unrealized_pnl, 2) }}</td>
          <td class="ts">{{ optional($p->opened_at)->toDateTimeString() }}</td>
        </tr>
      @empty
        <tr><td colspan="8" class="text-dim">No open positions.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="card">
  <h3>Latest Decisions & Guardrail Blocks</h3>

  @if(!empty($blocked))
    <div class="small" style="margin:.25rem 0 .75rem;">
      <strong>Recent blocks:</strong>
      @foreach ($blocked as $b)
        <div class="code small">
          <div class="ts">#{{ $b['id'] }} • {{ $b['ts'] }}</div>
          <div><strong>Violations:</strong> {{ implode(', ', $b['violations']) }}</div>
          @if (!empty($b['computed']))
            <div class="mono">exposure_before={{ number_format($b['computed']['exposure_before'] ?? 0, 3) }} • exposure_after={{ number_format($b['computed']['exposure_after'] ?? 0, 3) }}</div>
          @endif
        </div>
      @endforeach
    </div>
  @endif

  <table class="table">
    <thead><tr>
      <th>When</th><th>Action</th><th>Strategy</th><th>Reasoning</th><th class="right">Confidence</th>
    </tr></thead>
    <tbody>
      @forelse ($logs as $log)
        @php $p = $log->payload ?? []; @endphp
        <tr>
          <td class="ts">{{ optional($log->created_at)->toDateTimeString() }}</td>
          <td>
            <span class="badge">{{ strtoupper($log->action ?? 'N/A') }}</span>
            @if(($p['guardrails'] ?? '') === 'blocked')
              <span class="badge" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">BLOCKED</span>
            @endif
          </td>
          <td>{{ data_get($p, 'strategy.name', '—') }}</td>
          <td class="small">{{ Str::limit(data_get($p, 'reasoning', '—'), 140) }}</td>
          <td class="right">{{ number_format((float) data_get($p, 'telemetry.confidence', 0), 2) }}</td>
        </tr>
      @empty
        <tr><td colspan="5" class="text-dim">No decisions logged yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="card">
  <h3>Recent Trades</h3>
  <table class="table">
    <thead><tr>
      <th>Opened</th><th>Ticker</th><th>Side</th><th>Entry</th><th>Exit</th><th>Qty</th><th>Net PnL</th>
    </tr></thead>
    <tbody>
      @forelse ($trades as $t)
        <tr>
          <td class="ts">{{ optional($t->opened_at)->toDateTimeString() }}</td>
          <td class="mono">{{ $t->ticker }}</td>
          <td>{{ strtoupper($t->side) }}</td>
          <td>${{ number_format((float)$t->entry_price,2) }}</td>
          <td>{{ $t->exit_price ? '$'.number_format((float)$t->exit_price,2) : '—' }}</td>
          <td>{{ (float)$t->qty }}</td>
          <td class="mono">{{ $t->net_pnl !== null ? number_format((float)$t->net_pnl,2) : '—' }}</td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-dim">No trades yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
