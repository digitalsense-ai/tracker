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
        //$models = AiModel::orderByDesc('return_pct')->get();
        $models = AiModel::orderByDesc('active')->get();
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
        $data['equity'] = ($data['equity'] == '' || $data['equity'] == '0.00') ? 10000 : $data['equity'];
        $data = $this->normalizeBooleanFields($data);
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

        $data['equity'] = ($data['equity'] == '' || $data['equity'] == '0.00') ? 10000 : $data['equity'];
        $data = $this->normalizeBooleanFields($data);

        $model->update($data);       
        
        return back()->with('ok','Updated');
    }

    public function show(string $slug)
    {
        $models = AiModel::orderByDesc('active')->get();

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
            ->whereNot('action' ,'TICK_TOKEN_DEBUG')
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
            'models'             => $models,
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
            'premarket_prompt' => 'nullable|string',
            'check_interval_min' => 'required|integer|min:1|max:1440',
            'premarket_run_time' => 'nullable|string|max:8',
            'max_strategies_per_day' => 'nullable|integer|min:0|max:100',
            'max_symbols_per_day' => 'nullable|integer|min:0|max:100',
            'allow_sleeper_strategies' => 'nullable|boolean',
            'default_risk_per_strategy_pct' => 'nullable|numeric|min:0|max:100',
            'allow_activate_sleepers' => 'nullable|boolean',
            'allow_early_exit_on_invalidation' => 'nullable|boolean',
            'max_adds_per_position' => 'nullable|integer|min:0|max:10',
            'loop_min_price_move_pct' => 'nullable|numeric|min:0|max:100',
            'tags'  => 'nullable|array',
            'tags.*'=> 'string|max:30',
            'min_entry_score' => 'nullable|integer|min:0|max:10',
            'min_hold_score' => 'nullable|integer|min:0|max:10',
            'take_profit_enabled' => 'nullable|boolean',
            'tp_model' => 'nullable|in:full_exit,simple_runner,no_tp',
            'tp1_close_pct' => 'nullable|numeric|min:0|max:1',
            'move_sl_to_break_even_on_tp1' => 'nullable|boolean',
            'runner_trailing_enabled' => 'nullable|boolean',
            'runner_trail_distance_rr' => 'nullable|numeric|min:0|max:20',
        ]);
    }

    private function normalizeBooleanFields(array $data): array
    {
        foreach ([
            'active',
            'allow_sleeper_strategies',
            'allow_activate_sleepers',
            'allow_early_exit_on_invalidation',
            'take_profit_enabled',
            'move_sl_to_break_even_on_tp1',
            'runner_trailing_enabled',
        ] as $field) {
            $data[$field] = array_key_exists($field, $data) ? (bool) $data[$field] : false;
        }

        return $data;
    }

    public function toggle($id, Request $request)
    {
        $model = AiModel::findOrFail($id);

        // Toggle status        
        if($request->prompt_name == 'Pre-Market Prompt')
            $model->premarket_prompt_status = $request->status == 1 ? 0 : 1;
        elseif($request->prompt_name == 'Start Prompt')
            $model->start_prompt_status = $request->status == 1 ? 0 : 1;
        elseif($request->prompt_name == 'Loop / Check Prompt')
            $model->loop_prompt_status = $request->status == 1 ? 0 : 1;
        $model->save();

        if($request->prompt_name == 'Pre-Market Prompt')
            $new_status = $model->premarket_prompt_status;
        elseif($request->prompt_name == 'Start Prompt')
            $new_status = $model->start_prompt_status;
        elseif($request->prompt_name == 'Loop / Check Prompt')
            $new_status = $model->loop_prompt_status;

        return response()->json([
            'success' => true,
            'new_status' => isset($new_status) ?? null
        ]);
    }

}
