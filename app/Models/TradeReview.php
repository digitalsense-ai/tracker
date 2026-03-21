<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TradeReview extends Model
{
   protected $fillable = [
       'trade_id',
       'ai_model_id',
       'symbol',
       'strategy',
       'regime_at_entry',
       'regime_at_exit',
       'plan_aligned',
       'should_have_opened',
       'should_have_closed_earlier',
       'entry_quality_score',
       'exit_quality_score',
       'failure_reason',
       'improvement_action',
       'r_multiple',
       'net_pnl',
       'review_payload',
       'review_source',
   ];
   protected $casts = [
       'plan_aligned' => 'boolean',
       'should_have_opened' => 'boolean',
       'should_have_closed_earlier' => 'boolean',
       'entry_quality_score' => 'integer',
       'exit_quality_score' => 'integer',
       'r_multiple' => 'decimal:4',
       'net_pnl' => 'decimal:4',
       'review_payload' => 'array',
   ];
   public function trade(): BelongsTo
   {
       return $this->belongsTo(Trade::class);
   }
   public function aiModel(): BelongsTo
   {
       return $this->belongsTo(AiModel::class);
   }
}