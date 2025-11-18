<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\EquitySnapshot;
use Illuminate\Support\Collection;

class LiveController extends Controller
{
    // public function index()
    // {
    // 	$models = AiModel::orderByDesc('return_pct')->get();
    //     return view('live.index', compact('models'));       
    // }

    public function index()
    {
       $models = AiModel::with('equitySnapshots')
           ->where('active', true)
           ->get();
       // Build total equity history: sum of all models per timestamp (very simple version)
       $allSnapshots = EquitySnapshot::orderBy('taken_at')->get();
       /** @var array<string,array{time:string,value:float}> $totalSeries */
       $totalSeries = [];
       foreach ($allSnapshots as $s) {
           $t = $s->taken_at->format('Y-m-d H:i:00'); // round to minute
           if (!isset($totalSeries[$t])) {
               $totalSeries[$t] = ['time' => $t, 'value' => 0.0];
           }
           $totalSeries[$t]['value'] += (float) $s->equity;
       }
       $totalEquityHistory = array_values($totalSeries);
       // Leading models by return
       foreach ($models as $m) {
           $snapshots = $m->equitySnapshots;
           $start   = $m->start_equity
                      ?: optional($snapshots->first())->equity
                      ?: 0;
           $current = $m->equity
                      ?? optional($snapshots->last())->equity
                      ?? $start;
           $returnAbs = $current - $start;
           $returnPct = $start > 0 ? ($returnAbs / $start) * 100 : 0;
           $m->return_abs = $returnAbs;
           $m->return_pct = $returnPct;
       }
       $leadingModels = $models->sortByDesc('return_pct')->take(5);
       return view('live.index', [
           'totalEquityHistory' => $totalEquityHistory,
           'leadingModels'      => $leadingModels,
           'models' => $models
       ]);
    }
}
