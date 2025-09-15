@extends('layouts.app')
@section('content')
<h1>Profiles Leaderboard</h1>

<form method="get" class="mb-3" style="margin-bottom:1rem;">
  <label for="days" style="margin-right:8px;">Window (days)</label>
  <input id="days" name="days" type="number" min="1" max="90" value="{{ $days ?? 10 }}" style="width:80px;">
  <button class="btn" style="margin-left:8px;">Filter</button>
</form>

@if(($rows ?? null) && $rows->count())
  <div class="card">
    <table class="table">
      <thead>
        <tr>
          <th>Profile</th><th>Trades</th><th>Win%</th><th>Avg R</th><th>PF</th><th>Net</th><th>Score</th><th>Window</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $r)
          <tr>
            <td>{{ $r->profile_code ?? $r->profile_id }}</td>
            <td>{{ $r->trades }}</td>
            <td>{{ is_numeric($r->winrate) ? number_format($r->winrate,1) : $r->winrate }}%</td>
            <td>{{ is_numeric($r->avg_r) ? number_format($r->avg_r,2) : $r->avg_r }}</td>
            <td>{{ is_numeric($r->profit_factor) ? number_format($r->profit_factor,2) : $r->profit_factor }}</td>
            <td>{{ is_numeric($r->net_pl) ? number_format($r->net_pl,2) : $r->net_pl }}</td>
            <td>{{ is_numeric($r->score) ? number_format($r->score,2) : $r->score }}</td>
            <td class="text-muted">{{ $r->window }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    {{ $rows->withQueryString()->links() }}
  </div>
@else
  <p class="text-muted">No profile results for the selected window.</p>
@endif
@endsection
