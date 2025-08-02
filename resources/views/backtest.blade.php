@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Backtest Results</h1>

    @if ($error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endif

    @if (count($results) === 0)
        <p>No results available.</p>
    @else
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Ticker</th>
                    <th>Date</th>
                    <th>Gap %</th>
                    <th>Volume</th>
                    <th>Entry</th>
                    <th>SL</th>
                    <th>TP1</th>
                    <th>TP2</th>
                    <th>TP3</th>
                    <th>Exit</th>
                    <th>Exit Type</th>
                    <th>Win?</th>
                    <th>Result (DKK)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $trade)
                    <tr>
                        <td>{{ $trade->ticker }}</td>
                        <td>{{ $trade->date }}</td>
                        <td>{{ $trade->gap ?? '-' }}</td>
                        <td>{{ $trade->volume ?? '-' }}</td>
                        <td>{{ $trade->entry_price }}</td>
                        <td>{{ $trade->sl_price }}</td>
                        <td>{{ $trade->tp1_price }}</td>
                        <td>{{ $trade->tp2_price }}</td>
                        <td>{{ $trade->tp3_price }}</td>
                        <td>{{ $trade->exit_price }}</td>
                        <td>{{ strtoupper($trade->exit_type) }}</td>
                        <td>
                            @if ($trade->is_win)
                                ✅
                            @else
                                ❌
                            @endif
                        </td>
                        <td>{{ $trade->pnl_amount }} DKK</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
