@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    <h1 class="text-3xl font-bold mb-6">📈 Simulated Trade Results</h1>

    <div class="bg-white p-4 rounded shadow">
        <table class="table-auto w-full text-sm">
            <thead class="bg-gray-200 text-left">
                <tr>
                    <th class="px-2 py-1">Ticker</th>
                    <th class="px-2 py-1">Entry</th>
                    <th class="px-2 py-1">Exit</th>
                    <th class="px-2 py-1">Fees</th>
                    <th class="px-2 py-1">Net Profit</th>
                    <th class="px-2 py-1">Forecast Type</th>
                    <th class="px-2 py-1">Forecast Score</th>
                    <th class="px-2 py-1">Trend</th>
                    <th class="px-2 py-1">Earnings Day</th>
                    <th class="px-2 py-1">Nordnet</th>
                    <th class="px-2 py-1">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($trades as $trade)
                    <tr class="border-b">
                        <td class="px-2 py-1 font-semibold">{{ $trade->ticker }}</td>
                        <td class="px-2 py-1">{{ $trade->entry_price ?? '-' }}</td>
                        <td class="px-2 py-1">{{ $trade->exit_price ?? '-' }}</td>
                        <td class="px-2 py-1">{{ $trade->fees ?? '-' }}</td>
                        <td class="px-2 py-1 font-bold {{ $trade->net_profit > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $trade->net_profit ?? '-' }}
                        </td>
                        <td class="px-2 py-1">{{ $trade->forecast_type ?? '-' }}</td>
                        <td class="px-2 py-1">{{ $trade->forecast_score ?? '-' }}</td>
                        <td class="px-2 py-1">{{ $trade->trend_rating ?? '-' }}</td>
                        <td class="px-2 py-1">{{ $trade->earnings_day ? '✔' : '✘' }}</td>
                        <td class="px-2 py-1">{{ $trade->executed_on_nordnet ? '✔' : '✘' }}</td>
                        <td class="px-2 py-1">{{ $trade->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection