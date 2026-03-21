<?php
namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\TradeReview;
use Illuminate\Http\Request;

class TradeReviewController extends Controller
{
   public function index(Request $request)
   {
       $modelId = $request->get('model_id');
       $failureReason = $request->get('failure_reason');
       $symbol = $request->get('symbol');
       $reviewSource = $request->get('review_source');
       $query = TradeReview::query()
           ->with(['trade', 'aiModel'])
           ->latest();
       if ($modelId) {
           $query->where('ai_model_id', $modelId);
       }
       if ($failureReason) {
           $query->where('failure_reason', $failureReason);
       }
       if ($symbol) {
           $query->where('symbol', 'like', '%' . strtoupper($symbol) . '%');
       }
       if ($reviewSource) {
           $query->where('review_source', $reviewSource);
       }
       $tradeReviews = $query->paginate(25)->withQueryString();
       $models = AiModel::query()
           ->orderBy('name')
           ->get(['id', 'name']);
       $failureReasons = TradeReview::query()
           ->whereNotNull('failure_reason')
           ->distinct()
           ->orderBy('failure_reason')
           ->pluck('failure_reason');
       $reviewSources = TradeReview::query()
           ->distinct()
           ->orderBy('review_source')
           ->pluck('review_source');
       return view('trade_reviews.index', [
           'tradeReviews' => $tradeReviews,
           'models' => $models,
           'failureReasons' => $failureReasons,
           'reviewSources' => $reviewSources,
           'filters' => [
               'model_id' => $modelId,
               'failure_reason' => $failureReason,
               'symbol' => $symbol,
               'review_source' => $reviewSource,
           ],
       ]);
   }
   public function show(TradeReview $tradeReview)
   {
       $tradeReview->load(['trade', 'aiModel']);
       return view('trade_reviews.show', [
           'tradeReview' => $tradeReview,
       ]);
   }
}