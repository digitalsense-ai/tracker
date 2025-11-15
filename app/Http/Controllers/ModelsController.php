<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\Position;
use App\Models\Trade;
use App\Models\ModelLog;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ModelsController extends Controller
{
    public function index()
    {
        $models = AiModel::orderByDesc('return_pct')->get();
        return view('models.index', compact('models'));
    }

    public function create()
    {
        return view('models.edit', ['model' => new AiModel()]);
    }

    public function store(Request $req)
    {
        $data = $this->validated($req);
        $data['slug'] = Str::slug($data['name']);
        $model = AiModel::create($data);
        return redirect()->route('models.edit', $model)->with('ok','Saved');
    }

    public function edit(AiModel $model)
    {
        return view('models.edit', compact('model'));
    }

    public function update(Request $req, AiModel $model)
    {
        $data = $this->validated($req, $model->id);
        if (!$model->slug) $data['slug'] = Str::slug($data['name']);
        $model->update($data);
        return back()->with('ok','Updated');
    }

    // public function show(string $slug)
    // {
    //     //$model = AiModel::where('slug',$slug)->firstOrFail();
    //     //return view('models.show', compact('model'));

    //     $model = AiModel::where('slug',$slug)->firstOrFail();
    //     $logs  = \App\Models\ModelLog::where('ai_model_id',$model->id)->latest()->limit(25)->get();
    //     return view('models.show', compact('model','logs'));
    // }

    public function show(string $slug)
    {
        // $model = AiModel::where('slug',$slug)->firstOrFail();

        // $positions = Position::where('ai_model_id',$model->id)->where('status','open')->orderBy('opened_at','desc')->get();
        // $trades    = Trade::where('ai_model_id',$model->id)->orderBy('opened_at','desc')->limit(20)->get();
        // $logs      = ModelLog::where('ai_model_id',$model->id)->orderBy('id','desc')->limit(30)->get();

        // $blocked = [];
        // foreach ($logs as $log) {
        //     $p = $log->payload ?? [];
        //     if (isset($p['guardrails']) && $p['guardrails'] === 'blocked') {
        //         $blocked[] = [
        //             'id' => $log->id,
        //             'ts' => optional($log->created_at)->toDateTimeString(),
        //             'violations' => $p['violations'] ?? [],
        //             'computed' => $p['computed'] ?? [],
        //         ];
        //     }
        // }

        // return view('ai_models.show', [
        //     'm' => $model,
        //     'positions' => $positions,
        //     'trades' => $trades,
        //     'logs' => $logs,
        //     'blocked' => $blocked,
        // ]);

        $model = AiModel::where('slug',$slug)->firstOrFail();

        $positions = Position::where('ai_model_id',$model->id)
            ->where('status','open')
            ->orderBy('opened_at','desc')
            ->get();

        $trades = Trade::where('ai_model_id',$model->id)
            ->orderBy('opened_at','desc')
            ->limit(20)
            ->get();

        $logs = ModelLog::where('ai_model_id',$model->id)
            ->orderBy('id','desc')
            ->limit(20)
            ->get();

        // TEMP: dummy equity history until you have real snapshots
        $startEquity = $model->start_equity ?? 10000;

        $equityHistory = [
            ['time' => '2025-10-18', 'value' => $startEquity],
            ['time' => '2025-10-19', 'value' => $startEquity * 0.95],
            ['time' => '2025-10-20', 'value' => $startEquity * 0.8],
            ['time' => '2025-10-25', 'value' => $startEquity * 0.4],
            ['time' => '2025-10-30', 'value' => $startEquity * 0.5],
            ['time' => '2025-11-03', 'value' => $startEquity * 0.37],
        ];

        return view('ai_models.show', [
            'm'             => $model,
            'positions'     => $positions,
            'trades'        => $trades,
            'logs'          => $logs,
            'equityHistory' => $equityHistory,
            'startEquity'   => $startEquity,
        ]);
    }

    private function validated(Request $req, $ignoreId = null): array
    {
        return $req->validate([
            'name'  => 'required|string|max:120',
            'wallet'=> 'nullable|string|max:120',
            'equity'=> 'nullable|numeric',
            'return_pct'=> 'nullable|numeric',
            'active'=> 'nullable|boolean',
            'start_prompt'=> 'nullable|string',
            'loop_prompt' => 'nullable|string',
            'check_interval_min' => 'required|integer|min:1|max:1440',
            'tags'  => 'nullable|array',
            'tags.*'=> 'string|max:30',
        ]);
    }
}
