@php use Illuminate\Support\Str; @endphp
<link rel="stylesheet" href="/css/ai.css">

<div class="nav-tabs">
  <a href="{{ route('models.show', $m->slug) }}">Overview</a>
  <a href="{{ route('models.chat', $m->slug) }}" class="active">Model Chat</a>
  <a href="{{ route('models.log', $m->slug) }}">Raw Log</a>
</div>

<div class="card">
  <h3>Model Chat – {{ $m->name }}</h3>
  <div class="small text-dim">
    Thoughts + trading actions from the AI model over time. Newest entries first.
  </div>
</div>

@forelse ($entries as $e)
  @php
    $hasTrades = $e['openCount'] || $e['closeCount'] || $e['adjustCount'];
  @endphp

  <div class="card">
    <div class="flex">
      <div class="ts">#{{ $e['id'] }} • {{ $e['ts'] }}</div>
      <div>
        <span class="badge">{{ $e['action'] }}</span>
        @if($hasTrades)
          <span class="badge" style="background:#ecfdf3;color:#166534;border-color:#bbf7d0;">
            Trades:
            @if($e['openCount']) +{{ $e['openCount'] }} open @endif
            @if($e['closeCount']) / {{ $e['closeCount'] }} close @endif
            @if($e['adjustCount']) / {{ $e['adjustCount'] }} adjust @endif
          </span>
        @else
          <span class="badge" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
            No trades (thought only)
          </span>
        @endif
      </div>
    </div>

    <div class="small text-dim" style="margin-top:.25rem;">
      Strategy:
      <strong>{{ $e['strategy'] ?? '–' }}</strong>
      @if($e['signal'] || $e['confidence'])
        • Signal: {{ number_format($e['signal'], 2) }}
        • Confidence: {{ number_format($e['confidence'], 2) }}
      @endif
    </div>

    <div style="margin-top:.5rem;">
      <div class="small text-dim">Thoughts</div>
      <div>
        {{ $e['reasoning'] ? $e['reasoning'] : '— No reasoning text provided in this tick.' }}
      </div>
    </div>

    @if($hasTrades)
      <hr class="sep">
      <div class="small text-dim">Trading actions</div>
      <table class="table">
        <thead>
          <tr>
            <th>Type</th>
            <th>Ticker</th>
            <th>Side</th>
            <th>Entry</th>
            <th>Stop</th>
            <th>Target</th>
            <th>Leverage</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($e['orders'] as $o)
            <tr>
              <td class="mono">{{ Str::upper($o['type'] ?? '') }}</td>
              <td class="mono">{{ $o['ticker'] ?? '—' }}</td>
              <td>{{ Str::upper($o['side'] ?? '—') }}</td>
              <td class="mono">
                {{ isset($o['entry']) ? '$'.number_format((float)$o['entry'], 4) : '—' }}
              </td>
              <td class="mono">
                {{ isset($o['stop']) ? '$'.number_format((float)$o['stop'], 4) : '—' }}
              </td>
              <td class="mono">
                {{ isset($o['target']) ? '$'.number_format((float)$o['target'], 4) : '—' }}
              </td>
              <td class="mono">
                {{ isset($o['leverage']) ? number_format((float)$o['leverage'],2).'x' : '—' }}
              </td>
              <td class="small">
                {{ Str::limit($o['notes'] ?? '', 120) }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
@empty
  <div class="card">
    <div class="text-dim small">No model chat entries yet for this model.</div>
  </div>
@endforelse
