@extends('layouts.app')
@section('title', $model->name . ' · Plan Kanban v2')
@section('header_title', $model->name . ' · Plan Kanban v2')

@section('content')
{{-- v2 Kanban Template (drop-in) --}}
{{-- Lanes driven by plan item status --}}
<div class="grid grid-cols-4 gap-4">

  {{-- Lane 1: Idea Pool --}}
  <div class="card">
    <div class="card-header"><strong>Idea Pool</strong></div>
    <div class="card-body">
      @forelse($laneIdeaPool as $item)
        <article class="card mb-2">
          <div><strong>{{ $item['symbol'] }}</strong> · {{ $item['type'] }} · {{ $item['direction'] }}</div>
          <div class="text-sm">Entry: {{ $item['entry']['zone_low'] ?? '?' }} - {{ $item['entry']['zone_high'] ?? '?' }}</div>
          <div class="text-sm">Stop: {{ $item['exit_plan']['stop_loss'] ?? '?' }} · Targets:
            @foreach(($item['exit_plan']['targets'] ?? []) as $t)
              <span>{{ $t['price'] ?? '?' }} ({{ $t['size_pct'] ?? '?' }}%)</span>
            @endforeach
          </div>
          <div class="text-xs opacity-70">{{ $item['notes'] ?? '' }}</div>
        </article>
      @empty
        <div class="text-sm opacity-70">No items.</div>
      @endforelse
    </div>
  </div>

  {{-- Lane 2: Approved --}}
  <div class="card">
    <div class="card-header"><strong>Approved</strong></div>
    <div class="card-body">
      @forelse($laneApproved as $item)
        <article class="card mb-2">
          <div><strong>{{ $item['symbol'] }}</strong> · {{ $item['type'] }} · {{ $item['direction'] }}</div>
          <div class="text-sm">Entry: {{ $item['entry']['zone_low'] ?? '?' }} - {{ $item['entry']['zone_high'] ?? '?' }}</div>
          <div class="text-sm">Exit: SL {{ $item['exit_plan']['stop_loss'] ?? '?' }} · INV {{ $item['exit_plan']['invalidation'] ?? '?' }}</div>
          <div class="text-xs opacity-70">{{ $item['notes'] ?? '' }}</div>
        </article>
      @empty
        <div class="text-sm opacity-70">No items.</div>
      @endforelse
    </div>
  </div>

  {{-- Lane 3: Activated (Live) --}}
  <div class="card">
    <div class="card-header"><strong>Activated (Live)</strong></div>
    <div class="card-body">
      @forelse($laneActivated as $row)
        @php($item = $row['plan'])
        @php($pos = $row['position'])
        <article class="card mb-2">
          <div><strong>{{ $item['symbol'] }}</strong> · {{ strtoupper($pos->side) }} · Qty {{ $pos->qty }}</div>
          <div class="text-sm">Avg: {{ $pos->avg_price }} · Last: {{ $row['last'] ?? '—' }}</div>
          <div class="text-sm">Exit Plan: SL {{ $item['exit_plan']['stop_loss'] ?? '?' }} · TP
            @foreach(($item['exit_plan']['targets'] ?? []) as $t) <span>{{ $t['price'] ?? '?' }}</span> @endforeach
          </div>
          <div class="text-xs opacity-70">plan_item_id: {{ $item['plan_item_id'] ?? '—' }}</div>
        </article>
      @empty
        <div class="text-sm opacity-70">No live positions.</div>
      @endforelse
    </div>
  </div>

  {{-- Lane 4: Closed --}}
  <div class="card">
    <div class="card-header"><strong>Closed</strong></div>
    <div class="card-body">
      @forelse($laneClosed as $t)
        <article class="card mb-2">
          <div><strong>{{ $t->ticker }}</strong> · {{ $t->side }} · Qty {{ $t->qty }}</div>
          <div class="text-sm">Entry: {{ $t->entry_price }} · Exit: {{ $t->exit_price }}</div>
          <div class="text-sm">Reason: {{ $t->exit_reason_code }} — {{ $t->exit_reason_text }}</div>
          <div class="text-xs opacity-70">plan_item_id: {{ $t->plan_item_id }}</div>
        </article>
      @empty
        <div class="text-sm opacity-70">No closed trades.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection