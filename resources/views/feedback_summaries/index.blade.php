@extends('layouts.app')
@section('title','Feedback summaries')
@section('header_title','Feedback summaries')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Feedback Summaries</h1>
            <p class="text-sm text-gray-600">
                Model-level review dashboard showing failure reasons, win rates, and recommended changes.
            </p>
        </div>
        <form method="GET" action="{{ route('feedback-summaries.index') }}" class="flex items-end gap-2">
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
        <div class="bg-white shadow rounded-lg p-5 lg:col-span-2">
            <h2 class="text-lg font-semibold mb-4">Model Summary Cards</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($cards as $card)
                <div class="border rounded-lg p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="font-semibold text-lg">{{ $card['ai_model_name'] }}</h3>
                            <p class="text-xs text-gray-500">
                                Latest summary: {{ optional($card['latest_summary_date'])->format('Y-m-d') ?? '—' }}
                            </p>
                        </div>
                        <span class="inline-flex px-2 py-1 rounded text-xs
                            {{ $card['status'] === 'applied' ? 'bg-green-50 text-green-700' : 'bg-yellow-50 text-yellow-700' }}">
                            {{ $card['status'] }}
                        </span>
                    </div>
                    <dl class="grid grid-cols-2 gap-3 mt-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Trades Reviewed</dt>
                            <dd class="font-medium">{{ $card['trades_reviewed'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Win Rate</dt>
                            <dd class="font-medium">
                                {{ $card['win_rate'] !== null ? number_format($card['win_rate'] * 100, 1) . '%' : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Avg R</dt>
                            <dd class="font-medium">{{ $card['avg_r_multiple'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Top Failure</dt>
                            <dd class="font-medium">{{ $card['top_failure_reason'] ?? '—' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4">
                        <h4 class="text-sm font-medium mb-2">Failure Breakdown</h4>
                        @if(!empty($card['failure_breakdown']))
                            <div class="space-y-1 text-sm">
                                @foreach(array_slice($card['failure_breakdown'], 0, 5, true) as $reason => $count)
                                    <div class="flex justify-between">
                                        <span>{{ $reason }}</span>
                                        <span class="font-medium">{{ $count }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No failure breakdown yet.</p>
                        @endif
                    </div>
                    <div class="mt-4">
                        <h4 class="text-sm font-medium mb-2">Recommended Changes</h4>
                        <div class="text-sm text-gray-700 whitespace-pre-line">
                            {{ $card['recommended_changes'] ?: 'No recommendations yet.' }}
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('feedback-summaries.show', $card['ai_model_id']) }}?days={{ $days }}"
                        class="text-blue-600 hover:underline text-sm font-medium">
                            View details
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center text-gray-500 py-8">
                    No feedback summaries found.
                </div>
                @endforelse
            </div>
        </div>
        <div class="bg-white shadow rounded-lg p-5">
            <h2 class="text-lg font-semibold mb-4">Overall Failure Breakdown</h2>
            @if($overallBreakdown->count())
                <div class="space-y-2">
                    @foreach($overallBreakdown as $reason => $count)
                        <div class="flex justify-between border-b pb-2">
                            <span class="text-sm">{{ $reason }}</span>
                            <span class="text-sm font-semibold">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No reviewed failures in this period.</p>
            @endif
        </div>
    </div>
</div>
@endsection