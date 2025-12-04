@extends('layouts.app')
@section('title', $model->name . ' · Plan Kanban')
@section('header_title', $model->name . ' · Plan Kanban')

@section('content')
  @if (session('status'))
    <div class="card" style="padding:12px;margin-bottom:12px;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;">
      {{ session('status') }}
    </div>
  @endif
  @if (session('error'))
    <div class="card" style="padding:12px;margin-bottom:12px;color:#991b1b;background:#fee2e2;border:1px solid #fecaca;">
      {{ session('error') }}
    </div>
  @endif

  <section class="card" style="padding:16px;margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
      <div>
        <div class="bold">{{ $model->name }}</div>
        <div class="small">Wallet: <span class="mono">{{ $model->wallet ?: '#' }}</span></div>
        <div class="small">Date: <span class="bold">{{ $date }}</span></div>
      </div>
      <div>
        <div class="small">Equity</div>
        <div class="bold">${{ number_format($model->equity ?? 0, 2) }}</div>
      </div>
      <div>
        <div class="small">Return %</div>
        <div class="{{ ($model->return_pct ?? 0) >= 0 ? 'good' : 'bad' }} bold">
          {{ number_format($model->return_pct ?? 0, 2) }}%
        </div>
      </div>
      <div>
        <div class="small">Plan status</div>
        <div class="bold">
          @if (!$plan)
            No plan for this date
          @elseif (count($approved) === 0)
            Plan generated · 0 approved
          @else
            {{ count($approved) }} approved / {{ count($approved) + count($ideaPool) }} total
          @endif
        </div>
      </div>
    </div>
  </section>

  <form method="post" action="{{ route('models.kanban.update', ['slug' => $model->slug]) }}">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">

    <section class="grid" style="grid-template-columns:repeat(4, minmax(0, 1fr));gap:16px">

      {{-- Column 1: Idea Pool --}}
      <div class="card">
        <div class="card-header">
          <div class="bold">Idea Pool</div>
          <div class="small">{{ count($ideaPool) }} strategies</div>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px;max-height:640px;overflow:auto">
          @forelse($ideaPool as $s)
            @php
              $id = $s['id'] ?? null;
            @endphp
            <article class="card" style="padding:12px">
              <div class="bold">{{ $s['symbol'] ?? '?' }} · {{ strtoupper($s['direction'] ?? '?') }}</div>

              <div class="small">Mode: <span class="bold">{{ $s['mode'] ?? 'n/a' }}</span></div>
              <div class="small">Type: <span class="bold">{{ $s['type'] ?? 'n/a' }}</span></div>
              <div class="small">Priority: <span class="bold">{{ $s['priority'] ?? '-' }}</span></div>

              @if(!empty($s['entry_zone']) || isset($s['stop_loss']) || isset($s['take_profit']))
                <div style="margin-top:4px">
                  @if(!empty($s['entry_zone']))
                    <div class="small">Entry Zone: <span class="bold">{{ $s['entry_zone'] }}</span></div>
                  @endif
                  @if(isset($s['stop_loss']))
                    <div class="small">Stop Loss: <span class="bold">{{ $s['stop_loss'] }}</span></div>
                  @endif
                  @if(isset($s['take_profit']))
                    <div class="small">Take Profit: <span class="bold">{{ $s['take_profit'] }}</span></div>
                  @endif
                </div>
              @endif

              @if(!empty($s['notes']))
                <div class="small" style="margin-top:4px">{{ $s['notes'] }}</div>
              @endif

              <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
                <label class="small">
                  <input type="checkbox" name="approved[]" value="{{ $id }}" style="margin-right:4px">
                  <span class="bold">Approve</span>
                </label>
                @if(!empty($s['invalid_level']))
                  <span class="small">Invalid above/below: <span class="bold">{{ $s['invalid_level'] }}</span></span>
                @endif
              </div>
            </article>
          @empty
            <div class="small">No unapproved ideas.</div>
          @endforelse
        </div>
      </div>

      {{-- Column 2: Approved for Today --}}
      <div class="card">
        <div class="card-header">
          <div class="bold">Approved for Today</div>
          <div class="small">{{ count($approved) }} strategies</div>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px;max-height:640px;overflow:auto">
          @forelse($approved as $s)
            @php
              $id = $s['id'] ?? null;
            @endphp
            <article class="card" style="padding:12px">
              <div class="bold">{{ $s['symbol'] ?? '?' }} · {{ strtoupper($s['direction'] ?? '?') }}</div>

              <div class="small">Mode: <span class="bold">{{ $s['mode'] ?? 'n/a' }}</span></div>
              <div class="small">Type: <span class="bold">{{ $s['type'] ?? 'n/a' }}</span></div>
              <div class="small">Priority: <span class="bold">{{ $s['priority'] ?? '-' }}</span></div>

              @if(!empty($s['entry_zone']) || isset($s['stop_loss']) || isset($s['take_profit']))
                <div style="margin-top:4px">
                  @if(!empty($s['entry_zone']))
                    <div class="small">Entry Zone: <span class="bold">{{ $s['entry_zone'] }}</span></div>
                  @endif
                  @if(isset($s['stop_loss']))
                    <div class="small">Stop Loss: <span class="bold">{{ $s['stop_loss'] }}</span></div>
                  @endif
                  @if(isset($s['take_profit']))
                    <div class="small">Take Profit: <span class="bold">{{ $s['take_profit'] }}</span></div>
                  @endif
                </div>
              @endif

              @if(!empty($s['notes']))
                <div class="small" style="margin-top:4px">{{ $s['notes'] }}</div>
              @endif

              <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px">
                <label class="small">
                  <input type="checkbox" name="approved[]" value="{{ $id }}" checked style="margin-right:4px">
                  <span class="bold">Keep approved</span>
                </label>
                @if(!empty($s['invalid_level']))
                  <span class="small">Invalid above/below: <span class="bold">{{ $s['invalid_level'] }}</span></span>
                @endif
              </div>
            </article>
          @empty
            <div class="small">No approved strategies yet.</div>
          @endforelse
        </div>
      </div>

      {{-- Column 3: Live / Active Trades --}}
      <div class="card">
        <div class="card-header">
          <div class="bold">Live / Active Trades</div>
          <div class="small">{{ $openPositions->count() }} open</div>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px;max-height:640px;overflow:auto">
          @forelse($openPositions as $pos)
            <article class="card" style="padding:12px">
              <div class="bold">
                {{ strtoupper($pos->side) }} · {{ $pos->ticker }}
              </div>

              <div class="small">Entry Time: <span class="bold">{{ optional($pos->opened_at)->format('Y-m-d H:i:s') }}</span></div>
              <div class="small">Entry Price: <span class="bold">{{ number_format($pos->avg_price,4) }}</span></div>

              <div class="small">Quantity: <span class="bold">{{ number_format($pos->qty,4) }}</span></div>
              <div class="small">Leverage: <span class="bold">{{ number_format($pos->leverage ?? 1,2) }}x</span></div>
              <div class="small">Margin: <span class="bold">${{ number_format($pos->margin ?? 0,2) }}</span></div>

              @if(!is_null($pos->stop_price) || !is_null($pos->target_price))
                <div style="margin-top:4px">
                  @if(!is_null($pos->stop_price))
                    <div class="small">Stop Loss: <span class="bold">{{ number_format($pos->stop_price,4) }}</span></div>
                  @endif
                  @if(!is_null($pos->target_price))
                    <div class="small">Take Profit: <span class="bold">{{ number_format($pos->target_price,4) }}</span></div>
                  @endif
                </div>
              @endif

              <div class="small" style="margin-top:4px">
                Unrealized P&L:
                @php $p = $pos->unrealized_pnl ?? 0; @endphp
                <span class="bold {{ $p >= 0 ? 'good' : 'bad' }}">
                  {{ $p >= 0 ? '+' : '' }}${{ number_format($p,2) }}
                </span>
              </div>

              {{-- Placeholder for EXIT PLAN text - wired later when we store it --}}
              {{-- <div class="small" style="margin-top:4px">
                <a href="#" class="exit-plan-link">Exit plan</a>
              </div> --}}
            </article>
          @empty
            <div class="small">No open positions.</div>
          @endforelse
        </div>
      </div>

      {{-- Column 4: Completed Trades --}}
      <div class="card">
        <div class="card-header">
          <div class="bold">Completed Trades</div>
          <div class="small">{{ $completedTrades->count() }} recent</div>
        </div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px;max-height:640px;overflow:auto">
          @forelse($completedTrades as $t)
            <article class="card" style="padding:12px">
              <div class="bold">{{ strtoupper($t->side) }} · {{ $t->ticker }}</div>

              <div class="small">Entry Price: <span class="bold">{{ number_format($t->entry_price,4) }}</span></div>
              <div class="small">Exit Price: <span class="bold">{{ number_format($t->exit_price,4) }}</span></div>

              <div class="small">Quantity: <span class="bold">{{ number_format($t->qty,4) }}</span></div>
              <div class="small">Holding Time: <span class="bold">{{ $t->holding_seconds ?? 0 }}s</span></div>

              <div class="small">Notional (Entry): <span class="bold">${{ number_format($t->notional_entry ?? 0,2) }}</span></div>
              <div class="small">Notional (Exit): <span class="bold">${{ number_format($t->notional_exit ?? 0,2) }}</span></div>

              <div class="small">Total Fees: <span class="bold">${{ number_format($t->fees ?? 0,2) }}</span></div>

              <div class="small" style="margin-top:4px">
                Net P&L:
                @php $p = $t->net_pnl ?? 0; @endphp
                <span class="bold {{ $p >= 0 ? 'good' : 'bad' }}">
                  {{ $p >= 0 ? '+' : '' }}${{ number_format($p,2) }}
                </span>
              </div>
            </article>
          @empty
            <div class="small">No closed trades yet.</div>
          @endforelse
        </div>
      </div>

    </section>

    <div style="margin-top:16px">
      <button type="submit" class="tab" style="border:1px solid var(--line);padding:8px 12px;border-radius:999px">
        Save approvals
      </button>
    </div>
  </form>
@endsection
