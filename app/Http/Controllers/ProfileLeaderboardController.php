<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProfileLeaderboardController extends Controller {
    public function index(Request $request) {
        $days = (int)$request->input('days', 10);
        $windowStart = now()->subDays($days)->startOfDay();
        $windowEnd   = now();

        $rows = DB::table('profile_results as r')
            ->join('strategy_profiles as p','p.id','=','r.strategy_profile_id')
            ->select(
                'p.name',
                'r.trades',
                'r.pnl',
                'r.win_rate',
                'r.window_start',
                'r.window_end',
                DB::raw('COALESCE(r.window_start, r.created_at) as window_start_eff'),
                DB::raw('COALESCE(r.window_end, r.updated_at, r.created_at) as window_end_eff')
            )
            ->when($days > 0, function ($q) use ($windowStart, $windowEnd) {
                $q->whereBetween(DB::raw('COALESCE(r.window_end, r.updated_at, r.created_at)'), [$windowStart, $windowEnd]);
            })
            ->orderByDesc('r.pnl')
            ->get();

        return view('profiles.leaderboard', compact('rows'));
    }
}