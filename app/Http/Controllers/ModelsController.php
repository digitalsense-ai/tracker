<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
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

    public function show(string $slug)
    {
        $model = AiModel::where('slug',$slug)->firstOrFail();
        return view('models.show', compact('model'));
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
