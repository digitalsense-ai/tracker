
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    <h1 class="text-3xl font-bold mb-4">📈 Trade Results</h1>

    <p class="text-sm text-gray-600 mb-4">Overview of completed simulated trades and their outcome.</p>

    <table class="w-full table-auto text-sm">
        <thead class="bg-gray-200">
            <tr>
                <th class="px-2 py-1 text-left">Ticker</th>
                <th class="px-2 py-1 text-left">Date</th>
                <th class="px-2 py-1 text-left">Entry</th>
                <th class="px-2 py-1 text-left">Exit</th>
                <th class="px-2 py-1 text-left">SL</th>
                <th class="px-2 py-1 text-left">Result</th>
                <th class="px-2 py-1 text-left">Forecast</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($trades as $trade)
                <tr class="border-b {{ $trade->result === 'win' ? 'bg-green-100' : ($trade->result === 'loss' ? 'bg-red-100' : '') }}">
                    <td class="px-2 py-1">{{ $trade->ticker }}</td>
                    <td class="px-2 py-1">{{ $trade->date }}</td>
                    <td class="px-2 py-1">{{ $trade->entry_price }}</td>
                    <td class="px-2 py-1">{{ $trade->exit_price }}</td>
                    <td class="px-2 py-1">{{ $trade->stop_loss }}</td>
                    <td class="px-2 py-1 font-semibold">{{ strtoupper($trade->result) }}</td>
                    <td class="px-2 py-1">{{ $trade->forecast_type }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
