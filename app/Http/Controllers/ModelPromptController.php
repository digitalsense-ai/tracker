<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\ModelLog;

class ModelPromptController extends Controller
{
    public function index(string $slug, int $promptno)
    {
        $models = AiModel::orderByDesc('return_pct')->get();
        
        $model = AiModel::where('slug', $slug)->firstOrFail();

        // $logs = ModelLog::where('ai_model_id', $model->id)
        //     ->whereNot('action' ,'TICK_TOKEN_DEBUG')
        //     ->orderByDesc('id')
        //     ->paginate(50);

        return view('models.prompt', [
            'models'             => $models,            
            'm'    => $model,
            'promptno'             => $promptno
            //'logs' => $logs,
        ]);
    }
}
