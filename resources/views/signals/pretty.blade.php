@extends('layouts.app')

@section('content')
    <h1>Signals (Pretty)</h1>
    <table class="table">
        <thead>
        <tr>
            <th>Date</th>
            <th>Ticker</th>
            <th>Status</th>
            <th>Entry</th>
            <th>SL</th>
            <th>Exit</th>
            <th>Net</th>
        </tr>
        </thead>
        <tbody>
        @foreach($signals as $s)
            <tr>
                <td>{{ $s->date }}</td>
                <td>{{ $s->ticker }}</td>
                <td>{{ $s->status }}</td>
                <td>{{ $s->entry_price }}</td>
                <td>{{ $s->sl_price }}</td>
                <td>{{ $s->exit_price }}</td>
                <td>{{ $s->net_profit }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
