@extends('layouts.app')

@section('title', $model->name . ' · Plan Kanban')
@section('header_title', $model->name . ' · Plan Kanban')

@section('content')
  <section class="card" style="margin-bottom:16px;padding:16px">
    <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:center">
      <div>
        <div class="bold">{{ $model->name }}</div>
        <div class="small mono">Slug: {{ $model->slug }}</div>
        <div class="small">Trade date: {{ $date }}</div>
        @if(!$plan)
          <div class="small bad">No pre-market plan exists for this date. Run <code>php artisan ai:premarket --model_id={{ $model->id }}</code>.</div>
        @else
          <div class="small">Strategies in plan: {{ count($plan->plan_json ?? []) }}</div>
        @endif
      </div>
      <div class="small">
        <a href="{{ route('models.show',$model->slug) }}" class="btn">← Back to model</a>
      </div>
    </div>
    @if(session('ok'))
      <div class="good small" style="margin-top:8px">{{ session('ok') }}</div>
    @endif
    @if(session('error'))
      <div class="bad small" style="margin-top:8px">{{ session('error') }}</div>
    @endif
  </section>

  @if($plan)
    <form method="post" action="{{ route('models.kanban.update',$model->slug) }}">
      @csrf
      <input type="hidden" name="date" value="{{ $date }}">

      <section class="grid" style="grid-template-columns:repeat(4,1fr);gap:12px;align-items:flex-start">
        {{-- Column 1: Idea Pool --}}
        <div class="card" style="padding:12px;min-height:260px">
          <div class="card-header">
            <div class="bold">1. Idea Pool</div>
            <div class="small">AI candidates (not yet approved)</div>
          </div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
            @forelse($ideaPool as $idx => $s)
              @php
                $id = $s['id'] ?? $idx;
              @endphp
              <label class="card" style="padding:8px;cursor:pointer;display:block">
                <input type="checkbox" name="approved[]" value="{{ $id }}" style="margin-right:6px">
                <div class="small bold">{{ $s['symbol'] ?? '?' }} · {{ strtoupper($s['direction'] ?? '?') }}</div>
                <div class="tiny mono">
                  Mode: {{ $s['mode'] ?? 'n/a' }} · Type: {{ $s['type'] ?? 'n/a' }} · Pri: {{ $s['priority'] ?? '-' }}
                </div>
                <div class="tiny mono">Entry: {{ $s['entry_zone'] ?? '?' }} · SL: {{ $s['stop_loss'] ?? '?' }} · TP: {{ $s['take_profit'] ?? '?' }}</div>
                @if(!empty($s['notes']))
                  <div class="tiny" style="margin-top:4px">{{ $s['notes'] }}</div>
                @endif
              </label>
            @empty
              <div class="small">No unapproved ideas.</div>
            @endforelse
          </div>
        </div>

        {{-- Column 2: Approved --}}
        <div class="card" style="padding:12px;min-height:260px">
          <div class="card-header">
            <div class="bold">2. Approved for Today</div>
            <div class="small">Only approved strategies are used by the loop.</div>
          </div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
            @forelse($approvedStrategies as $idx => $s)
              @php
                $id = $s['id'] ?? $idx;
              @endphp
              <label class="card" style="padding:8px;cursor:pointer;display:block">
                <input type="checkbox" name="approved[]" value="{{ $id }}" checked style="margin-right:6px">
                <div class="small bold">{{ $s['symbol'] ?? '?' }} · {{ strtoupper($s['direction'] ?? '?') }}</div>
                <div class="tiny mono">
                  Mode: {{ $s['mode'] ?? 'n/a' }} · Type: {{ $s['type'] ?? 'n/a' }} · Pri: {{ $s['priority'] ?? '-' }}
                </div>
                <div class="tiny mono">Entry: {{ $s['entry_zone'] ?? '?' }} · SL: {{ $s['stop_loss'] ?? '?' }} · TP: {{ $s['take_profit'] ?? '?' }}</div>
                @if(!empty($s['notes']))
                  <div class="tiny" style="margin-top:4px">{{ $s['notes'] }}</div>
                @endif
              </label>
            @empty
              <div class="small">No approved strategies yet. Tick boxes in the Idea Pool to approve them.</div>
            @endforelse
          </div>
        </div>

        {{-- Column 3: Live Trades --}}
        <div class="card" style="padding:12px;min-height:260px">
          <div class="card-header">
            <div class="bold">3. Live / Active Trades</div>
            <div class="small">Open positions (paper) for this model.</div>
          </div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
            @forelse($openPositions as $p)
              <div class="card" style="padding:8px">
                <div class="small bold">{{ $p->symbol }} · {{ strtoupper($p->side) }}</div>
                <div class="tiny mono">Qty: {{ $p->quantity }} · Entry: {{ $p->entry_price }} · Px: {{ $p->current_price }}</div>
                <div class="tiny mono">SL: {{ $p->stop_loss }} · TP: {{ $p->take_profit }}</div>
                <div class="tiny">
                  P&L:
                  <span class="{{ ($p->unrealized_pnl ?? 0) >= 0 ? 'good' : 'bad' }}">
                    {{ number_format($p->unrealized_pnl ?? 0, 2) }}
                  </span>
                </div>
              </div>
            @empty
              <div class="small">No open positions.</div>
            @endforelse
          </div>
        </div>

        {{-- Column 4: Completed Trades --}}
        <div class="card" style="padding:12px;min-height:260px">
          <div class="card-header">
            <div class="bold">4. Completed Trades</div>
            <div class="small">Recent closed trades for this model.</div>
          </div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:8px;max-height:420px;overflow:auto">
            @forelse($recentClosedTrades as $t)
              <div class="card" style="padding:8px">
                <div class="small bold">{{ $t->symbol }} · {{ strtoupper($t->side) }}</div>
                <div class="tiny mono">Entry: {{ $t->entry_price }} · Exit: {{ $t->exit_price }}</div>
                <div class="tiny mono">Size: {{ $t->quantity }} · Held: {{ $t->holding_time ?? '-' }}</div>
                <div class="tiny">
                  P&L:
                  <span class="{{ ($t->net_pnl ?? 0) >= 0 ? 'good' : 'bad' }}">
                    {{ number_format($t->net_pnl ?? 0, 2) }}
                  </span>
                </div>
              </div>
            @empty
              <div class="small">No closed trades yet.</div>
            @endforelse
          </div>
        </div>
      </section>

      <div style="margin-top:16px">
        <button type="submit" class="btn">Save approvals</button>
        <span class="small mono" style="margin-left:8px">
          Approved strategies are the only ones sent to the AI loop in state.daily_plan.
        </span>
      </div>
    </form>
  @endif
@endsection
