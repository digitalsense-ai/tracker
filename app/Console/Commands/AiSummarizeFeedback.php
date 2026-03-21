<?php
namespace App\Console\Commands;
use App\Models\ModelFeedbackSummary;
use App\Models\TradeReview;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
class AiSummarizeFeedback extends Command
{
   protected $signature = 'ai:summarize-feedback {--days=7}';
   protected $description = 'Summarize reviewed trades into model feedback summaries';
   public function handle(): int
   {
       $days = (int) $this->option('days');
       $from = now()->subDays($days);
       $reviews = TradeReview::query()
           ->where('created_at', '>=', $from)
           ->get()
           ->groupBy('ai_model_id');
       foreach ($reviews as $aiModelId => $group) {
           $this->summarizeModel((int) $aiModelId, $group);
       }
       $this->info('Feedback summaries created.');
       return self::SUCCESS;
   }
   protected function summarizeModel(int $aiModelId, Collection $reviews): void
   {
       $count = $reviews->count();
       if ($count === 0) {
           return;
       }
       $wins = $reviews->filter(fn ($r) => (float) $r->net_pnl > 0)->count();
       $winRate = $count > 0 ? round($wins / $count, 4) : null;
       $avgR = round((float) $reviews->pluck('r_multiple')->filter()->avg(), 4);
       $failureBreakdown = $reviews
           ->pluck('failure_reason')
           ->filter()
           ->countBy()
           ->sortDesc()
           ->toArray();
       $topFailureReason = array_key_first($failureBreakdown);
       $recommendedChanges = $reviews
           ->pluck('improvement_action')
           ->filter()
           ->countBy()
           ->sortDesc()
           ->map(fn ($count, $action) => "{$action}: {$count}")
           ->implode("\n");
       ModelFeedbackSummary::updateOrCreate(
           [
               'ai_model_id' => $aiModelId,
               'summary_date' => now()->toDateString(),
           ],
           [
               'trades_reviewed' => $count,
               'win_rate' => $winRate,
               'avg_r_multiple' => $avgR,
               'top_failure_reason' => $topFailureReason,
               'failure_breakdown' => $failureBreakdown,
               'recommended_changes' => $recommendedChanges,
               'draft_prompt_changes' => null,
               'status' => 'draft',
           ]
       );
   }
}