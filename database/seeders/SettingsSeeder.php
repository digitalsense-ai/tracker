<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\SettingsService;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $svc = app(SettingsService::class);

        // Session
        $svc->set('session.start', '09:45', 'time', 'session', 'Session Start', ['help'=>'HH:MM local time']);
        $svc->set('session.end', '10:45', 'time', 'session', 'Session End', ['help'=>'HH:MM local time']);
        $svc->set('session.force_exit', '11:30', 'time', 'session', 'Force Exit', ['help'=>'Close positions after this time']);

        // Risk
        $svc->set('risk.position_size', 3000, 'int', 'risk', 'Position Size (DKK)');
        $svc->set('risk.fees_bps', 10, 'int', 'risk', 'Fees (bps)', ['help'=>'10 bps = 0.10% round-trip handled in service']);
        $svc->set('risk.min_fee', 2.0, 'float', 'risk', 'Min Fee (DKK)');
        
        // Filters
        $svc->set('filters.one_trade_per_ticker', true, 'bool', 'filters', 'One trade per ticker');
        $svc->set('filters.ema_fast', 20, 'int', 'filters', 'EMA Fast (5m)');
        $svc->set('filters.ema_slow', 50, 'int', 'filters', 'EMA Slow (5m)');
        $svc->set('filters.use_vwap', true, 'bool', 'filters', 'Use VWAP filter');
        $svc->set('filters.atr5m_min_pct', 0.25, 'float', 'filters', 'ATR(14,5m) min %');
        $svc->set('filters.gap_pct_max', 3.0, 'float', 'filters', 'Max Pre-market Gap %');
        $svc->set('filters.skip_extreme_orb_factor', 1.8, 'float', 'filters', 'Skip if ORB > factor × median(20)');

        // Retest
        $svc->set('retest.buffer_pct', 0.02, 'float', 'retest', 'Retest Buffer %');
        $svc->set('retest.depth_min', 0.30, 'float', 'retest', 'Retest Depth Min');
        $svc->set('retest.depth_max', 0.60, 'float', 'retest', 'Retest Depth Max');
        $svc->set('retest.rvol_min', 1.5, 'float', 'retest', 'Min RVOL (1m)');

        // Management
        $svc->set('management.tp1_r', 1.0, 'float', 'management', 'TP1 (R)');
        $svc->set('management.tp2_r', 2.0, 'float', 'management', 'TP2 (R)');
        $svc->set('management.use_trailing', true, 'bool', 'management', 'Use Trailing');
        $svc->set('management.trailing_mult', 1.5, 'float', 'management', 'Trailing ATR(5)×');
        $svc->set('management.cooldown_after_sl', true, 'bool', 'management', 'Cooldown after SL');

        $svc->set('ui.theme','light','string','ui','Theme (light|dark)', ['options'=>['light','dark']]);
    }
}
