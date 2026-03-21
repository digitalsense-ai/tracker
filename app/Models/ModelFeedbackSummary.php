<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelFeedbackSummary extends Model
{
   protected $fillable = [
       'ai_model_id',
       'summary_date',
       'trades_reviewed',
       'win_rate',
       'avg_r_multiple',
       'top_failure_reason',
       'failure_breakdown',
       'recommended_changes',
       'draft_prompt_changes',
       'status',
   ];
   protected $casts = [
       'summary_date' => 'date',
       'trades_reviewed' => 'integer',
       'win_rate' => 'decimal:4',
       'avg_r_multiple' => 'decimal:4',
       'failure_breakdown' => 'array',
       'draft_prompt_changes' => 'array',
   ];
   public function aiModel(): BelongsTo
   {
       return $this->belongsTo(AiModel::class);
   }
}