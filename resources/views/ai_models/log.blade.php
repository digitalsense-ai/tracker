@extends('layouts.app')
@section('title','Models')
@section('header_title','Models')

@section('content')
@php use Illuminate\Support\Str; @endphp
<link rel="stylesheet" href="/css/ai.css">

<div class="nav-tabs">
  <a href="{{ route('models.show', $m->slug) }}">Overview</a>
  <a href="{{ route('models.chat', $m->slug) }}">Model Chat</a>
  <a href="{{ route('models.log', $m->slug) }}" class="active">Raw Log</a>
</div>

<div class="card">
  <h3>Model Log – {{ $m->name }}</h3>
  <div class="small text-dim">
    Full raw decisions from the AI loop (including guardrail blocks, actions, and payload).
    Click “View JSON” to inspect a specific tick.
  </div>
</div>

<div class="card">
  <table class="table">
    <thead>
      <tr>
        <th>ID</th>
        <th>When</th>
        <th>Action</th>
        <th>Strategy</th>
        <th>Summary</th>
        <th>Flags</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @forelse ($logs as $log)
        @php
          $p = $log->payload ?? [];
          $strategy = data_get($p, 'strategy.name', '—');
          $reason   = data_get($p, 'reasoning', '');
          $short    = Str::limit($reason, 80);
          $guard    = $p['guardrails'] ?? null;
        @endphp
        <tr>
          <td class="mono">{{ $log->id }}</td>
          <td class="ts">{{ optional($log->created_at)->toDateTimeString() }}</td>
          <td>
            <span class="badge">{{ strtoupper($log->action ?? 'N/A') }}</span>
          </td>
          <td>{{ $strategy }}</td>
          <td class="small">{{ $short ?: '—' }}</td>
          <td>
            @if($guard === 'blocked')
              <span class="badge" style="background:#fef2f2;color:#991b1b;border-color:#fecaca;">GUARDRAILS</span>
            @endif
          </td>
          <td class="right">
            <button type="button"
                    onclick="toggleJson('log-json-{{ $log->id }}')"
                    class="badge"
                    style="cursor:pointer;">
              View JSON
            </button>
          </td>
        </tr>
        <tr id="log-json-{{ $log->id }}" style="display:none;">
          <td colspan="7">
            <div class="code small mono">
              {!! nl2br(e(json_encode($p, JSON_PRETTY_PRINT))) !!}
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-dim">No logs yet.</td></tr>
      @endforelse
    </tbody>
  </table>

  <div style="margin-top:.75rem;">
    {{ $logs->links() }}
  </div>
</div>

<script>
  function toggleJson(id) {
    const row = document.getElementById(id);
    if (!row) return;
    row.style.display = (row.style.display === 'none' || row.style.display === '') ? 'table-row' : 'none';
  }
</script>

@endsection