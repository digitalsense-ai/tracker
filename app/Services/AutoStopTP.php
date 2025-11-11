<?php

namespace App\Services;

use App\Models\AiModel;
use App\Models\Position;
use App\Services\PriceFeed\PriceFeedInterface;

class AutoStopTP
{
    public function __construct(private PaperBroker $broker, private PriceFeedInterface $feed) {}

    public function run(AiModel $model): int
    {
        $closed = 0;
        $positions = Position::where('ai_model_id',$model->id)->where('status','open')->get();
        foreach ($positions as $p) {
            $last = $this->feed->last($p->ticker);
            if ($last === null) continue;

            if ($p->side === 'long') {
                if ($p->stop_price && $last <= $p->stop_price) { $this->broker->close($model, $p->ticker, ['price'=>$p->stop_price]); $closed++; continue; }
                if ($p->target_price && $last >= $p->target_price) { $this->broker->close($model, $p->ticker, ['price'=>$p->target_price]); $closed++; continue; }
            } else {
                if ($p->stop_price && $last >= $p->stop_price) { $this->broker->close($model, $p->ticker, ['price'=>$p->stop_price]); $closed++; continue; }
                if ($p->target_price && $last <= $p->target_price) { $this->broker->close($model, $p->ticker, ['price'=>$p->target_price]); $closed++; continue; }
            }
        }
        return $closed;
    }
}
