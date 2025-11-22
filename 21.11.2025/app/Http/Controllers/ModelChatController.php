<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\ModelLog;

class ModelChatController extends Controller
{
    public function index(string $slug)
    {
        $model = AiModel::where('slug', $slug)->firstOrFail();

        $logs = ModelLog::where('ai_model_id', $model->id)
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $entries = $logs->map(function (ModelLog $log) {
            $p = $log->payload ?? [];

            $action    = strtoupper($log->action ?? ($p['action'] ?? 'HOLD'));
            $strategy  = data_get($p, 'strategy.name', null);
            $reasoning = data_get($p, 'reasoning', null);
            $orders    = collect($p['orders'] ?? []);

            $openCount   = $orders->filter(fn($o)=>strtolower($o['type'] ?? '')==='open')->count();
            $closeCount  = $orders->filter(fn($o)=>strtolower($o['type'] ?? '')==='close')->count();
            $adjustCount = $orders->filter(fn($o)=>strtolower($o['type'] ?? '')==='adjust')->count();

            $confidence = (float) data_get($p, 'telemetry.confidence', 0);
            $signal     = (float) data_get($p, 'telemetry.signal_strength', 0);

            return [
                'id'         => $log->id,
                'ts'         => optional($log->created_at)->toDateTimeString(),
                'action'     => $action,
                'strategy'   => $strategy,
                'reasoning'  => $reasoning,
                'orders'     => $orders,
                'openCount'  => $openCount,
                'closeCount' => $closeCount,
                'adjustCount'=> $adjustCount,
                'confidence' => $confidence,
                'signal'     => $signal,
                'raw'        => $p,
            ];
        });

        return view('ai_models.chat', [
            'm'        => $model,
            'entries'  => $entries,
        ]);
    }
}
