<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\ModelLog;

class ModelLogController extends Controller
{
    public function index(string $slug)
    {
        $model = AiModel::where('slug', $slug)->firstOrFail();

        $logs = ModelLog::where('ai_model_id', $model->id)
            ->whereNot('action' ,'TICK_TOKEN_DEBUG')
            ->orderByDesc('id')
            ->paginate(50);

        return view('ai_models.log', [
            'm'    => $model,
            'logs' => $logs,
        ]);
    }
}
