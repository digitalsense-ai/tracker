<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExplainerFlowController extends Controller {
    public function show(Request $request) {
        $ticker = $request->query('ticker', 'AAPL');
        $date   = $request->query('date', date('Y-m-d'));
        return view('explainer-flow', compact('ticker', 'date'));
    }
}
