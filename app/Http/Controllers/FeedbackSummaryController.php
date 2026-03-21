<?php
namespace App\Http\Controllers;
use App\Models\AiModel;
use App\Models\ModelFeedbackSummary;
use App\Models\TradeReview;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
class FeedbackSummaryController extends Controller
{
   public function index(Request $request)
   {
       $days = (int) $request->get('days', 7);
       $from = now()->subDays($days)->startOfDay();
       $summaries = ModelFeedbackSummary::query()
           ->with('aiModel')
           ->where('summary_date', '>=', $from->toDateString())
           ->orderByDesc('summary_date')
           ->get()
           ->groupBy('ai_model_id');
       $cards = $summaries->map(function (Collection $group, $aiModelId) {
           $latest = $group->sortByDesc('summary_date')->first();
           $tradesReviewed = (int) $group->sum('trades_reviewed');
           $weightedWinRate = $tradesReviewed > 0
               ? round(
                   $group->sum(function ($row) {
                       return (float) $row->win_rate * (int) $row->trades_reviewed;
                   }) / $tradesReviewed,
                   4
               )
               : null;
           $weightedAvgR = $tradesReviewed > 0
               ? round(
                   $group->sum(function ($row) {
                       return (float) $row->avg_r_multiple * (int) $row->trades_reviewed;
                   }) / $tradesReviewed,
                   4
               )
               : null;
           $failureBreakdown = [];
           foreach ($group as $summary) {
               foreach (($summary->failure_breakdown ?? []) as $reason => $count) {
                   $failureBreakdown[$reason] = ($failureBreakdown[$reason] ?? 0) + $count;
               }
           }
           arsort($failureBreakdown);
           return [
               'ai_model_id' => (int) $aiModelId,
               'ai_model_name' => $latest?->aiModel?->name ?? 'Unknown',
               'latest_summary_date' => $latest?->summary_date,
               'trades_reviewed' => $tradesReviewed,
               'win_rate' => $weightedWinRate,
               'avg_r_multiple' => $weightedAvgR,
               'top_failure_reason' => array_key_first($failureBreakdown),
               'failure_breakdown' => $failureBreakdown,
               'recommended_changes' => $latest?->recommended_changes,
               'status' => $latest?->status ?? 'draft',
           ];
       })->sortByDesc('trades_reviewed')->values();
       $overallBreakdown = TradeReview::query()
           ->where('created_at', '>=', $from)
           ->whereNotNull('failure_reason')
           ->pluck('failure_reason')
           ->countBy()
           ->sortDesc();
       return view('feedback_summaries.index', [
           'cards' => $cards,
           'days' => $days,
           'overallBreakdown' => $overallBreakdown,
       ]);
   }
   public function show(AiModel $aiModel, Request $request)
   {
       $days = (int) $request->get('days', 30);
       $from = now()->subDays($days)->startOfDay();
       $summaries = ModelFeedbackSummary::query()
           ->where('ai_model_id', $aiModel->id)
           ->where('summary_date', '>=', $from->toDateString())
           ->orderByDesc('summary_date')
           ->get();
       $reviews = TradeReview::query()
           ->with('trade')
           ->where('ai_model_id', $aiModel->id)
           ->where('created_at', '>=', $from)
           ->latest()
           ->paginate(20)
           ->withQueryString();
       $failureBreakdown = $reviews->getCollection()
           ->pluck('failure_reason')
           ->filter()
           ->countBy()
           ->sortDesc();
       $strategyBreakdown = $reviews->getCollection()
           ->groupBy(fn ($r) => $r->strategy ?: 'unknown')
           ->map(function ($group) {
               $count = $group->count();
               $wins = $group->filter(fn ($r) => (float) $r->net_pnl > 0)->count();
               $avgR = round((float) $group->pluck('r_multiple')->filter()->avg(), 4);
               return [
                   'count' => $count,
                   'win_rate' => $count > 0 ? round($wins / $count, 4) : null,
                   'avg_r_multiple' => $avgR,
               ];
           })
           ->sortByDesc('count');
       return view('feedback_summaries.show', [
           'aiModel' => $aiModel,
           'summaries' => $summaries,
           'reviews' => $reviews,
           'days' => $days,
           'failureBreakdown' => $failureBreakdown,
           'strategyBreakdown' => $strategyBreakdown,
       ]);
   }
}