@extends('layouts.app')

@section('style')
<!-- Bootstrap 5 CSS CDN -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
@endsection

@section('script')
<!-- Bootstrap 5 JS Bundle CDN (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@endsection

@section('content')
<div class="container">
  <h2>✔️ Simulated Trade Results</h2>

  <form method="get" class="mb-3 d-flex align-items-center">
    <label class="me-2">Date:</label>
    <input type="date" name="date" value="{{ $day ?? '' }}">
    <button class="btn btn-sm btn-primary ms-2">Filter</button>
    @if($day)
      <a class="btn btn-sm btn-outline-secondary ms-2" href="{{ url('/results') }}">Clear</a>
    @endif
  </form>

  <table class="table table-sm table-striped">
    <thead>
      <tr>
        <th>Ticker</th>
        <th>Entry</th>
        <th>Exit</th>
        <th>Fees</th>
        <th>Net Profit</th>
        <th>Status</th>
        <th>Forecast Type</th>
        <th>Forecast Score</th>
        <th>Trend</th>
        <th>Earnings Day</th>
        <th>Nordnet</th>
        <th>Timestamp</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($trades as $t)
        <tr>
          <td>{{ $t->ticker }}</td>
          <td>{{ $t->entry_price ? number_format($t->entry_price, 2) : '-' }}</td>
          <td>{{ $t->exit_price ? number_format($t->exit_price, 2) : '-' }}</td>
          <td>{{ $t->fees ? number_format($t->fees, 2) : '-' }}</td>
          <td>
            @if(!is_null($t->net_profit))
              {{ number_format($t->net_profit, 2) }}
            @else
              -
            @endif
          </td>
          <td>{{ $t->status }}</td>
          <td>{{ $t->forecast_type }}</td>
          <td>{{ $t->forecast_score ? number_format($t->forecast_score, 2) : '-' }}</td>
          <td>{{ $t->trend_rating ? number_format($t->trend_rating, 2) : '-' }}</td>
          <td>{{ $t->earnings_day ? '✓' : '✗' }}</td>
          <td>{{ $t->executed_on_nordnet ? '✓' : '✗' }}</td>
          <td>{{ $t->created_at }}</td>
        </tr>
      @empty
        <tr><td colspan="12">No results.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
