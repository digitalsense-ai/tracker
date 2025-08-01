@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    <h1 class="text-3xl font-bold mb-6">📉 Backtest Simulation</h1>

    @if (!empty($results))
        <table class="w-full text-sm table-auto border rounded shadow">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2 text-left">Ticker</th>
                    <th class="px-3 py-2 text-left">Date</th>
                    <th class="px-3 py-2 text-left">Gap %</th>
                    <th class="px-3 py-2 text-left">RVOL</th>
                    <th class="px-3 py-2 text-left">Volume</th>
                    <th class="px-3 py-2 text-left">Forecast</th>
                    <th class="px-3 py-2 text-left">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($results as $result)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $result['ticker'] }}</td>
                        <td class="px-3 py-2">{{ $result['date'] }}</td>
                        <td class="px-3 py-2">{{ $result['gap'] }}%</td>
                        <td class="px-3 py-2">{{ $result['rvol'] }}</td>
                        <td class="px-3 py-2">{{ number_format($result['volume']) }}</td>
                        <td class="px-3 py-2 capitalize">{{ $result['forecast_type'] }}</td>
                        <td class="px-3 py-2 font-semibold text-green-600">{{ $result['status'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-gray-600">No backtest results found.</p>
    @endif
</div>
@endsection
