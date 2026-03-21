@extends('layouts.app')
@section('content')
<div class="container mx-auto px-4 py-6">
<div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
<div>
<h1 class="text-2xl font-bold">Feedback: {{ $aiModel->name }}</h1>
<p class="text-sm text-gray-600">
               Detailed review summary, recent failures, and strategy-level performance.
</p>
</div>
<div class="flex gap-2">
<a href="{{ route('feedback-summaries.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">
               Back
</a>
</div>
</div>
<div class="mb-6">
<form method="GET" action="{{ route('feedback-summaries.show', $aiModel) }}" class="flex items-end gap-2">
<div>
<label class="block text-sm font-medium text-gray-700 mb-1">Days</label>
<select name="days" class="border rounded px-3 py-2">
                   @foreach([7, 14, 30, 60] as $option)
<option value="{{ $option }}" @selected($days == $option)>{{ $option }}</option>
                   @endforeach
</select>
</div>
<button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
               Apply
</button>
</form>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
<div class="bg-white shadow rounded-lg p-5">
<h2 class="text-lg font-semibold mb-4">Failure Breakdown</h2>
           @if($failureBreakdown->count())
<div class="space-y-2">
                   @foreach($failureBreakdown as $reason => $count)
<div class="flex justify-between border-b pb-2">
<span class="text-sm">{{ $reason }}</span>
<span class="text-sm font-semibold">{{ $count }}</span>
</div>
                   @endforeach
</div>
           @else
<p class="text-sm text-gray-500">No failures found in this period.</p>
           @endif
</div>
<div class="bg-white shadow rounded-lg p-5 lg:col-span-2">
<h2 class="text-lg font-semibold mb-4">Strategy Breakdown</h2>
<div class="overflow-x-auto">
<table class="min-w-full text-sm">
<thead class="bg-gray-100 text-gray-700">
<tr>
<th class="px-4 py-3 text-left">Strategy</th>
<th class="px-4 py-3 text-left">Trades</th>
<th class="px-4 py-3 text-left">Win Rate</th>
<th class="px-4 py-3 text-left">Avg R</th>
</tr>
</thead>
<tbody>
                       @forelse($strategyBreakdown as $strategy => $stats)
<tr class="border-t">
<td class="px-4 py-3">{{ $strategy }}</td>
<td class="px-4 py-3">{{ $stats['count'] }}</td>
<td class="px-4 py-3">
                                   {{ $stats['win_rate'] !== null ? number_format($stats['win_rate'] * 100, 1) . '%' : '—' }}
</td>
<td class="px-4 py-3">{{ $stats['avg_r_multiple'] ?? '—' }}</td>
</tr>
                       @empty
<tr>
<td colspan="4" class="px-4 py-6 text-center text-gray-500">
                                   No strategy data found.
</td>
</tr>
                       @endforelse
</tbody>
</table>
</div>
</div>
</div>
<div class="bg-white shadow rounded-lg p-5 mb-6">
<h2 class="text-lg font-semibold mb-4">Recent Summary Snapshots</h2>
<div class="overflow-x-auto">
<table class="min-w-full text-sm">
<thead class="bg-gray-100 text-gray-700">
<tr>
<th class="px-4 py-3 text-left">Date</th>
<th class="px-4 py-3 text-left">Trades Reviewed</th>
<th class="px-4 py-3 text-left">Win Rate</th>
<th class="px-4 py-3 text-left">Avg R</th>
<th class="px-4 py-3 text-left">Top Failure</th>
<th class="px-4 py-3 text-left">Status</th>
</tr>
</thead>
<tbody>
                   @forelse($summaries as $summary)
<tr class="border-t">
<td class="px-4 py-3">{{ optional($summary->summary_date)->format('Y-m-d') }}</td>
<td class="px-4 py-3">{{ $summary->trades_reviewed }}</td>
<td class="px-4 py-3">
                               {{ $summary->win_rate !== null ? number_format($summary->win_rate * 100, 1) . '%' : '—' }}
</td>
<td class="px-4 py-3">{{ $summary->avg_r_multiple ?? '—' }}</td>
<td class="px-4 py-3">{{ $summary->top_failure_reason ?? '—' }}</td>
<td class="px-4 py-3">{{ $summary->status }}</td>
</tr>
                   @empty
<tr>
<td colspan="6" class="px-4 py-6 text-center text-gray-500">
                               No summary snapshots available.
</td>
</tr>
                   @endforelse
</tbody>
</table>
</div>
</div>
<div class="bg-white shadow rounded-lg p-5">
<h2 class="text-lg font-semibold mb-4">Recent Reviewed Trades</h2>
<div class="overflow-x-auto">
<table class="min-w-full text-sm">
<thead class="bg-gray-100 text-gray-700">
<tr>
<th class="px-4 py-3 text-left">Date</th>
<th class="px-4 py-3 text-left">Symbol</th>
<th class="px-4 py-3 text-left">Strategy</th>
<th class="px-4 py-3 text-left">Entry</th>
<th class="px-4 py-3 text-left">Exit</th>
<th class="px-4 py-3 text-left">Failure</th>
<th class="px-4 py-3 text-left">Action</th>
<th class="px-4 py-3 text-left">PnL</th>
</tr>
</thead>
<tbody>
                   @forelse($reviews as $review)
<tr class="border-t">
<td class="px-4 py-3">{{ optional($review->created_at)->format('Y-m-d H:i') }}</td>
<td class="px-4 py-3 font-semibold">{{ $review->symbol }}</td>
<td class="px-4 py-3">{{ $review->strategy ?? '—' }}</td>
<td class="px-4 py-3">{{ $review->entry_quality_score ?? '—' }}/10</td>
<td class="px-4 py-3">{{ $review->exit_quality_score ?? '—' }}/10</td>
<td class="px-4 py-3">{{ $review->failure_reason ?? '—' }}</td>
<td class="px-4 py-3">{{ $review->improvement_action ?? '—' }}</td>
<td class="px-4 py-3">{{ $review->net_pnl ?? '—' }}</td>
</tr>
                   @empty
<tr>
<td colspan="8" class="px-4 py-6 text-center text-gray-500">
                               No reviewed trades found.
</td>
</tr>
                   @endforelse
</tbody>
</table>
</div>
<div class="mt-4">
           {{ $reviews->links() }}
</div>
</div>
</div>
@endsection