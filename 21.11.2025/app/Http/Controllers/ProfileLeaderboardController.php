<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileLeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $days = (int) $request->query('days', 0);

        $base = DB::table('profile_results as r')
            ->join('strategy_profiles as p', 'p.id', '=', 'r.strategy_profile_id');

        if ($days === 0) {
            $sub = DB::table('profile_results')
                ->select('strategy_profile_id', DB::raw('MAX(COALESCE(window_end, updated_at, created_at)) as latest_end'))
                ->groupBy('strategy_profile_id');

            $base->joinSub($sub, 'mx', function ($join) {
                $join->on('mx.strategy_profile_id', '=', 'r.strategy_profile_id');
            })
            ->whereRaw('COALESCE(r.window_end, r.updated_at, r.created_at) = mx.latest_end');
        } else {
            $end   = now();
            $start = now()->subDays($days)->startOfDay();

            $base->whereBetween(DB::raw('COALESCE(r.window_end, r.updated_at, r.created_at)'), [$start, $end]);
        }

        $rows = $base->select([
                'p.id as profile_id',
                'p.name',
                'r.trades',
                'r.pnl',
                'r.win_rate',
                'r.window_start',
                'r.window_end',
                DB::raw('COALESCE(r.window_start, r.created_at) as window_start_eff'),
                DB::raw('COALESCE(r.window_end, r.updated_at, r.created_at) as window_end_eff'),
            ])
            ->orderByDesc('r.pnl')
            ->get();

        return view('profiles.leaderboard', [
            'rows' => $rows,
            'days' => $days,
        ]);
    }
}
