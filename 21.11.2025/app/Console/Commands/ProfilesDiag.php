<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Support\BacktestShim;
use App\Models\StrategyProfile;

class ProfilesDiag extends Command
{
    protected $signature = 'profiles:diag {--days=10} {--profile=}';
    protected $description = 'Diagnose profiles leaderboard data path: tables, shim run, and recent results.';

    public function handle(): int
    {
        $days = (int)($this->option('days') ?: 10);
        $only = $this->option('profile');

        $this->line('--- PROFILES DIAG ---');

        $total = DB::table('strategy_profiles')->count();
        $enabled = DB::table('strategy_profiles')->where('enabled', true)->count();
        $this->line("strategy_profiles: total={$total}, enabled={$enabled}");

        $hasSim = DB::getSchemaBuilder()->hasTable('simulated_trades');
        $this->line('has simulated_trades: '.($hasSim?'yes':'no'));
        if ($hasSim) {
            $sample = DB::table('simulated_trades')->orderByDesc(DB::raw('1'))->limit(3)->get();
            $this->line('simulated_trades sample: '.json_encode($sample));
        }

        $profile = $only
            ? StrategyProfile::where('enabled',true)->where('id',$only)->first()
            : StrategyProfile::where('enabled',true)->orderBy('id')->first();

        if ($profile) {
            $start = Carbon::now('Europe/Copenhagen')->subDays($days)->startOfDay();
            $trades = BacktestShim::run($start, $days, $profile->settings ?? [], true);
            $this->line('shim trades count: '.(is_array($trades)?count($trades):0));
            $this->line('shim trades head: '.json_encode(array_slice((array)$trades,0,5)));
        } else {
            $this->line('shim trades count: 0 (no enabled profiles found)');
        }

        $hasPR = DB::getSchemaBuilder()->hasTable('profile_results');
        $this->line('has profile_results: '.($hasPR?'yes':'no'));
        if ($hasPR) {
            $cnt = DB::table('profile_results')->count();
            $last = DB::table('profile_results')->orderByDesc('id')->limit(5)->get();
            $this->line("profile_results count: {$cnt}");
            $this->line('profile_results tail: '.json_encode($last));
        }

        return self::SUCCESS;
    }
}
