<?php

namespace App\Http\Controllers;

use App\Models\AiModel;

class LiveController extends Controller
{
    public function index()
    {
    	$models = AiModel::orderByDesc('return_pct')->get();
        return view('live.index', compact('models'));       
    }
}
