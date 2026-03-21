@extends('layouts.app')
@section('title','Trade reviews')
@section('header_title','Trade reviews')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Trade Reviews</h1>
        <p class="text-sm text-gray-600">
            Review failed reasons, entry/exit quality, and improvement suggestions.
        </p>
    </div>

    <div class="bg-white shadow rounded-lg p-4 mb-6">
        <form method="GET" action="{{ route('trade-reviews.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Model</label>
                <select name="model_id" class="w-full border rounded px-3 py-2">
                    <option value="">All</option>
                    @foreach($models as $model)
                        <option value="{{ $model->id }}" @selected(($filters['model_id'] ?? null) == $model->id)>
                            {{ $model->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Failure Reason</label>
                <select name="failure_reason" class="w-full border rounded px-3 py-2">
                    <option value="">All</option>
                    @foreach($failureReasons as $reason)
                        <option value="{{ $reason }}" @selected(($filters['failure_reason'] ?? null) == $reason)>
                            {{ $reason }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Symbol</label>
                <input
                    type="text"
                    name="symbol"
                    value="{{ $filters['symbol'] ?? '' }}"
                    class="w-full border rounded px-3 py-2"
                    placeholder="AAPL"
                >
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Review Source</label>
                <select name="review_source" class="w-full border rounded px-3 py-2">
                    <option value="">All</option>
                    @foreach($reviewSources as $source)
                        <option value="{{ $source }}" @selected(($filters['review_source'] ?? null) == $source)>
                            {{ $source }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Filter
                </button>
                <a href="{{ route('trade-reviews.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm table">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Model</th>
                        <th class="px-4 py-3 text-left">Symbol</th>
                        <th class="px-4 py-3 text-left">Strategy</th>
                        <th class="px-4 py-3 text-left">Plan</th>
                        <th class="px-4 py-3 text-left">Entry</th>
                        <th class="px-4 py-3 text-left">Exit</th>
                        <th class="px-4 py-3 text-left">Failure Reason</th>
                        <th class="px-4 py-3 text-left">Action</th>
                        <th class="px-4 py-3 text-left">R</th>
                        <th class="px-4 py-3 text-left">PnL</th>
                        <th class="px-4 py-3 text-left">Details</th>
                    </tr>
                </thead>
            <tbody>
                @forelse($tradeReviews as $review)
                    <tr class="border-t">
                        <td class="px-4 py-3">
                            {{ optional($review->created_at)->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $review->aiModel->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 font-semibold">
                            {{ $review->symbol }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $review->strategy ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($review->plan_aligned)
                                <span class="text-green-700 font-medium">Yes</span>
                            @else
                                <span class="text-red-600 font-medium">No</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            {{ $review->entry_quality_score ?? '—' }}/10
                        </td>
                        <td class="px-4 py-3">
                            {{ $review->exit_quality_score ?? '—' }}/10
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-1 rounded bg-red-50 text-red-700">
                                {{ $review->failure_reason ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            {{ $review->improvement_action ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $review->r_multiple ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $pnl = $review->net_pnl;
                            @endphp
                            <span class="{{ $pnl > 0 ? 'text-green-700' : ($pnl < 0 ? 'text-red-600' : 'text-gray-700') }}">
                                {{ $pnl ?? '—' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('trade-reviews.show', $review) }}" class="text-blue-600 hover:underline">
                                View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="px-4 py-6 text-center text-gray-500">
                            No trade reviews found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            </table>
        </div>

        <div class="p-4 border-t">
            {{ $tradeReviews->links() }}
        </div>
    </div>
</div>
@endsection