<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfilesController extends Controller
{
    public function leaderboard(Request $request)
    {dd("test");
        $days = (int)($request->input('days', 10));
        if ($days <= 0) { $days = 10; }
        $since = now()->subDays($days)->toDateString();

        $rows = DB::table('profile_results as pr')
            ->join('strategy_profiles as sp', 'sp.id', '=', 'pr.strategy_profile_id')
            ->select([
                'sp.id as profile_id',
                DB::raw("COALESCE(sp.code, CONCAT('P', sp.id)) as profile_code"),
                'pr.trades',
                'pr.winrate',
                'pr.avg_r',
                'pr.net_pl',
                'pr.profit_factor',
                'pr.drawdown_pct',
                'pr.score',
                'pr.window',
                'pr.created_at',
            ])
            ->where('pr.created_at', '>=', $since)
            ->orderByDesc('pr.score')
            ->orderBy('sp.code')
            ->paginate(50);

        return view('profiles.leaderboard', [
            'rows' => $rows,
            'days' => $days,
        ]);
    }
}
