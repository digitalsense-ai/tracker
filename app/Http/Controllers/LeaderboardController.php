<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $days = (int)($request->input('days', 0));
        $windowEnd   = now();
        $windowStart = $days > 0 ? now()->subDays($days)->startOfDay() : null;

        $q = DB::table('profile_results as r')
            ->join('strategy_profiles as p', 'p.id', '=', 'r.strategy_profile_id')
            ->select([
                'p.id','p.name','p.external_key',
                'r.trades','r.pnl','r.win_rate','r.window_start','r.window_end','r.updated_at'
            ])
            ->orderByDesc('r.pnl');

        if ($days > 0) {
            // Robust filter: when days>0, filter by when the result was updated
            $q->whereBetween('r.updated_at', [$windowStart, $windowEnd]);
        }

        $rows = $q->get()->map(function($row) {
            $row->window = ($row->window_start && $row->window_end)
                ? $row->window_start . ' .. ' . $row->window_end
                : '—';
            return $row;
        });

        return view('leaderboard.index', [
            'rows' => $rows,
            'days' => $days,
            'windowStart' => $windowStart,
            'windowEnd' => $windowEnd,
        ]);
    }
}
