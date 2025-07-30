@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6">
    <h1 class="text-3xl font-bold mb-4">📊 ORB Trading Dashboard</h1>

    @php
        use App\Models\Stock;

        // Dummy forecast settings indtil database er klar
        $forecastSettings = [
            'min_gap' => 3,
            'min_rvol' => 1.5,
            'min_volume' => 2000000,
            'forecast_type' => 'gap-up',
        ];

        // Simuleret statuslogik uden database
        foreach ($stocks as $stock) {
            if ($stock->status === 'forecast' && $stock->gap > 3 && $stock->rvol > 1.5) {
                $stock->status = 'breakout';
            } elseif ($stock->status === 'breakout' && $stock->volume > 3000000) {
                $stock->status = 'retest';
            } elseif ($stock->status === 'retest' && $stock->gap > 2.5) {
                $stock->status = 'entry';
            } elseif ($stock->status === 'entry' && $stock->rvol > 2) {
                $stock->status = 'exit';
            }
        }
    @endphp

    <!-- Section: Forecast Aktier -->
    <div class="bg-white p-4 rounded shadow mb-6">
        <h2 class="text-xl font-semibold mb-2">🔮 Forecast Aktier</h2>

        <p class="text-sm text-gray-600 mb-2">
            {{ count($stocks->where('status', 'forecast')) }} aktier matcher dine forecast-kriterier.
        </p>

        <table class="w-full table-auto text-sm">
            <thead class="bg-gray-200">
                <tr>
                    <th class="px-2 py-1 text-left">Ticker</th>
                    <th class="px-2 py-1 text-left">Gap %</th>
                    <th class="px-2 py-1 text-left">RVOL</th>
                    <th class="px-2 py-1 text-left">Volumen</th>
                    <th class="px-2 py-1 text-left">Forventet Setup</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stocks->where('status', 'forecast') as $stock)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $stock->ticker }}</td>
                        <td class="px-2 py-1">{{ $stock->gap }}%</td>
                        <td class="px-2 py-1">{{ $stock->rvol }}</td>
                        <td class="px-2 py-1">{{ number_format($stock->volume) }}</td>
                        <td class="px-2 py-1">{{ $stock->forecast }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Forecast Settings -->
        <div class="mt-6">
            <h3 class="text-lg font-semibold mb-2">⚙️ Forecast Indstillinger</h3>
            <form action="/update-forecast-config" method="POST">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Min. GAP %</label>
                        <input type="number" step="0.1" name="min_gap" value="{{ $forecastSettings['min_gap'] }}" class="w-full border rounded px-2 py-1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Min. RVOL</label>
                        <input type="number" step="0.1" name="min_rvol" value="{{ $forecastSettings['min_rvol'] }}" class="w-full border rounded px-2 py-1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Min. Volumen</label>
                        <input type="number" step="1000" name="min_volume" value="{{ $forecastSettings['min_volume'] }}" class="w-full border rounded px-2 py-1">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Forecast-Type</label>
                        <select name="forecast_type" class="w-full border rounded px-2 py-1">
                            <option value="gap-up" {{ $forecastSettings['forecast_type'] == 'gap-up' ? 'selected' : '' }}>Gap Up</option>
                            <option value="gap-down" {{ $forecastSettings['forecast_type'] == 'gap-down' ? 'selected' : '' }}>Gap Down</option>
                            <option value="consolidation" {{ $forecastSettings['forecast_type'] == 'consolidation' ? 'selected' : '' }}>Consolidation</option>
                            <option value="volatility-squeeze" {{ $forecastSettings['forecast_type'] == 'volatility-squeeze' ? 'selected' : '' }}>Volatility Squeeze</option>
                            <option value="breakout-ready" {{ $forecastSettings['forecast_type'] == 'breakout-ready' ? 'selected' : '' }}>Breakout Ready</option>
                            <option value="mean-revert" {{ $forecastSettings['forecast_type'] == 'mean-revert' ? 'selected' : '' }}>Mean Revert</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4">
                    <button class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Gem Forecast Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Section: Strategi Settings -->
    <div class="bg-white p-4 rounded shadow mb-6">
        <h2 class="text-xl font-semibold mb-2">🛠️ Strategi Indstillinger</h2>
        @include('partials.strategy-settings')
    </div>

    <!-- Section: Breakout Aktier -->
    @include('partials.breakout', ['stocks' => $stocks->where('status', 'breakout')])
    <!-- Section: Retest Aktier -->
    @include('partials.retest', ['stocks' => $stocks->where('status', 'retest')])
    <!-- Section: Entry & Exit Aktier -->
    @include('partials.entry_exit', ['stocks' => $stocks->whereIn('status', ['entry', 'exit'])])
</div>
@endsection
