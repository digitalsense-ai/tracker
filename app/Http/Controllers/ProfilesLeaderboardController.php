<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ProfilesLeaderboardController extends Controller {
    public function index(Request $request) {dd("leaderboard");
        $days = (int)$request->input('days', 10);
        $windowStart = now()->subDays($days)->startOfDay();
        $windowEnd   = now();

        $rows = DB::table('profile_results as r')
            ->join('strategy_profiles as p','p.id','=','r.profile_id')
            ->whereBetween('r.window_end', [$windowStart, $windowEnd])
            ->orderByDesc('r.pnl')
            ->select('p.name','r.trades','r.pnl','r.win_rate','r.window_start','r.window_end')
            ->get();

        return view('profiles.leaderboard', compact('rows'));
    }
}