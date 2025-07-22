<div class="bg-green-100 p-4 rounded shadow mb-6">
    <h2 class="text-xl font-semibold mb-2">🎯 Entry & Exit</h2>

    @if ($stocks->count())
        <table class="w-full table-auto text-sm">
            <thead class="bg-green-200">
                <tr>
                    <th class="px-2 py-1 text-left">Ticker</th>
                    <th class="px-2 py-1 text-left">Status</th>
                    <th class="px-2 py-1 text-left">Rvol</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($stocks as $stock)
                    <tr class="border-b">
                        <td class="px-2 py-1">{{ $stock->ticker }}</td>
                        <td class="px-2 py-1">{{ ucfirst($stock->status) }}</td>
                        <td class="px-2 py-1">{{ $stock->rvol }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-gray-500">Ingen aktier med aktiv Entry eller Exit.</p>
    @endif
</div>
