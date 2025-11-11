@extends('layouts.app')

@section('content')
<div class="container">
  <h1>Profiles Leaderboard</h1>

  <form method="GET" action="{{ route('profiles.leaderboard') }}" class="mb-3">
    <label for="days" class="form-label">Window (days):</label>
    <input id="days" type="number" class="form-control" name="days" value="{{ request('days', 10) }}" min="0" />
    <small class="text-muted">Sæt til 0 for at slå dato-filter fra.</small>
    <div class="mt-2"><button class="btn btn-primary">Opdater</button></div>
  </form>

  @if($rows->isEmpty())
    <div class="alert alert-info">No profile results for the selected window.</div>
  @else
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead>
          <tr>
            <th>Profile</th>
            <th class="text-end">Trades</th>
            <th class="text-end">PNL</th>
            <th class="text-end">Win %</th>
            <th>Window</th>
          </tr>
        </thead>
        <tbody>
          @foreach($rows as $r)
            <tr>
              <td>{{ $r->name }}</td>
              <td class="text-end">{{ number_format((int)$r->trades) }}</td>
              <td class="text-end">{{ number_format((float)$r->pnl, 2) }}</td>
              <td class="text-end">{{ number_format((float)$r->win_rate, 2) }}</td>
              <td>
                {{ optional($r->window_start ?? $r->window_start_eff)->format('Y-m-d H:i') ?? '' }}
                —
                {{ optional($r->window_end ?? $r->window_end_eff)->format('Y-m-d H:i') ?? '' }}
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection
