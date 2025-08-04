@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Backtest Results (ORB + Retest Strategy)</h2>
    @if (!empty($results))
        <table class="table">
            <thead>
                <tr>
                    <th>Ticker</th>
                    <th>Entry</th>
                    <th>SL</th>
                    <th>TP1</th>
                    <th>TP2</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
                    <tr>
                        <td>{{ $result['ticker'] }}</td>
                        <td>{{ $result['entryPrice'] }}</td>
                        <td>{{ $result['slPrice'] }}</td>
                        <td>{{ $result['tp1'] }}</td>
                        <td>{{ $result['tp2'] }}</td>
                        <td>{{ $result['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>No results to display.</p>
    @endif
</div>
@endsection
