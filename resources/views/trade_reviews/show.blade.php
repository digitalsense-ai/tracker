@extends('layouts.app')
@section('content')
<div class="container mx-auto px-4 py-6">
<div class="mb-6 flex items-center justify-between">
<div>
<h1 class="text-2xl font-bold">Trade Review: {{ $tradeReview->symbol }}</h1>
<p class="text-sm text-gray-600">
               Review details for trade #{{ $tradeReview->trade_id }}
</p>
</div>
<a href="{{ route('trade-reviews.index') }}" class="bg-gray-200 text-gray-800 px-4 py-2 rounded hover:bg-gray-300">
           Back
</a>
</div>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
<div class="bg-white shadow rounded-lg p-5">
<h2 class="text-lg font-semibold mb-4">Review Summary</h2>
<dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
<div>
<dt class="text-gray-500">Model</dt>
<dd class="font-medium">{{ $tradeReview->aiModel->name ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Symbol</dt>
<dd class="font-medium">{{ $tradeReview->symbol }}</dd>
</div>
<div>
<dt class="text-gray-500">Strategy</dt>
<dd class="font-medium">{{ $tradeReview->strategy ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Plan Aligned</dt>
<dd class="font-medium">{{ $tradeReview->plan_aligned ? 'Yes' : 'No' }}</dd>
</div>
<div>
<dt class="text-gray-500">Entry Score</dt>
<dd class="font-medium">{{ $tradeReview->entry_quality_score ?? '—' }}/10</dd>
</div>
<div>
<dt class="text-gray-500">Exit Score</dt>
<dd class="font-medium">{{ $tradeReview->exit_quality_score ?? '—' }}/10</dd>
</div>
<div>
<dt class="text-gray-500">Failure Reason</dt>
<dd class="font-medium">{{ $tradeReview->failure_reason ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Improvement Action</dt>
<dd class="font-medium">{{ $tradeReview->improvement_action ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">R Multiple</dt>
<dd class="font-medium">{{ $tradeReview->r_multiple ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Net PnL</dt>
<dd class="font-medium">{{ $tradeReview->net_pnl ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Regime at Entry</dt>
<dd class="font-medium">{{ $tradeReview->regime_at_entry ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Regime at Exit</dt>
<dd class="font-medium">{{ $tradeReview->regime_at_exit ?? '—' }}</dd>
</div>
</dl>
</div>
<div class="bg-white shadow rounded-lg p-5">
<h2 class="text-lg font-semibold mb-4">Trade Record</h2>
           @if($tradeReview->trade)
<dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
<div>
<dt class="text-gray-500">Trade ID</dt>
<dd class="font-medium">{{ $tradeReview->trade->id }}</dd>
</div>
<div>
<dt class="text-gray-500">Opened At</dt>
<dd class="font-medium">{{ $tradeReview->trade->opened_at ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Closed At</dt>
<dd class="font-medium">{{ $tradeReview->trade->closed_at ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Qty</dt>
<dd class="font-medium">{{ $tradeReview->trade->qty ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Entry Price</dt>
<dd class="font-medium">{{ $tradeReview->trade->entry_price ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Stop Price</dt>
<dd class="font-medium">{{ $tradeReview->trade->stop_price ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">Target Price</dt>
<dd class="font-medium">{{ $tradeReview->trade->target_price ?? '—' }}</dd>
</div>
<div>
<dt class="text-gray-500">PnL</dt>
<dd class="font-medium">{{ $tradeReview->trade->net_pnl ?? $tradeReview->trade->pnl ?? '—' }}</dd>
</div>
</dl>
           @else
<p class="text-gray-500">Trade record not found.</p>
           @endif
</div>
</div>
<div class="bg-white shadow rounded-lg p-5 mt-6">
<h2 class="text-lg font-semibold mb-4">Review Payload</h2>
<pre class="bg-gray-50 border rounded p-4 text-xs overflow-x-auto">{{ json_encode($tradeReview->review_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
</div>
</div>
@endsection