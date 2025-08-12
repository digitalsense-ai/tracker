<?php

namespace App\Http\Controllers;

use App\Models\SimulatedTrade;
use Illuminate\Http\Request;

class TradeResultController extends Controller
{
    public function index(Request $request)
    {
        $day = $request->input('date'); // optional ?date=YYYY-MM-DD
        $q = SimulatedTrade::query();

        if ($day) {
            $q->where('date', $day);
        }

        // Get rows ordered by newest, then keep only the latest per (date,ticker)
        $rows = $q->orderBy('date', 'desc')
            ->orderBy('ticker')
            ->orderBy('created_at', 'desc')
            ->get()
            ->unique(function ($r) {
                return $r->date . '|' . $r->ticker;
            })
            ->values();

        $trades = $rows->map(function ($r) {
            $entry = (float)($r->entry_price ?? 0);
            $exit  = (float)($r->exit_price ?? 0);
            $fees  = (float)($r->fees ?? 0);
            $net   = $r->net_profit;

            if (is_null($net) && $entry > 0 && $exit > 0) {
                $net = ($exit - $entry) - $fees;
            }

            return (object)[
                'ticker'         => $r->ticker,
                'date'           => $r->date,
                'entry_price'    => $entry ?: null,
                'exit_price'     => $exit ?: null,
                'fees'           => $fees ?: null,
                'net_profit'     => $net,
                'status'         => $r->status ?? '-',
                'forecast_type'  => $r->forecast_type ?? '-',
                'forecast_score' => $r->forecast_score ?? null,
                'trend_rating'   => $r->trend_rating ?? null,
                'earnings_day'   => (bool)($r->earnings_day ?? false),
                'executed_on_nordnet' => (bool)($r->executed_on_nordnet ?? false),
                'created_at'     => $r->created_at,
            ];
        });

        return view('results', compact('trades', 'day'));
    }
}
