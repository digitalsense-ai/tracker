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
    protected $description = 'Diagnose why /profiles shows 0 — checks tables, runs shim, prints summary';

    public function handle(): int
    {
        $days = (int)$this->option('days') ?: 10;
        $only = $this->option('profile');

        $out = [];

        // 1) profiles
        $profiles = DB::table('strategy_profiles')->count();
        $enabled = DB::table('strategy_profiles')->where('enabled', true)->count();
        $out['strategy_profiles_total'] = $profiles;
        $out['strategy_profiles_enabled'] = $enabled;

        // 2) simulated_trades sample
        $simExists = DB::getSchemaBuilder()->hasTable('simulated_trades');
        $out['has_simulated_trades_table'] = $simExists;
        if ($simExists) {
            $sample = DB::table('simulated_trades')->orderByDesc(DB::raw('1'))->limit(3)->get();
            $out['simulated_trades_sample'] = $sample;
        }

        // 3) Try a shim run
        $profile = $only
            ? StrategyProfile::where('enabled',true)->where('id',$only)->first()
            : StrategyProfile::where('enabled',true)->orderBy('id')->first();

        if ($profile) {
            $start = Carbon::now('Europe/Copenhagen')->subDays($days)->startOfDay();
            $trades = BacktestShim::run($start, $days, $profile->settings ?? [], true);
            $out['shim_trades_count'] = is_array($trades) ? count($trades) : 0;
            $out['shim_trades_head'] = array_slice($trades, 0, 5);
        } else {
            $out['shim_trades_count'] = 0;
            $out['shim_trades_head'] = [];
            $out['note'] = 'No enabled strategy_profiles found';
        }

        // 4) profile_results summary
        $prExists = DB::getSchemaBuilder()->hasTable('profile_results');
        $out['has_profile_results_table'] = $prExists;
        if ($prExists) {
            $cnt = DB::table('profile_results')->count();
            $out['profile_results_count'] = $cnt;
            $last = DB::table('profile_results')->orderByDesc('id')->limit(5)->get();
            $out['profile_results_tail'] = $last;
        }

        $this->line(json_encode($out, JSON_PRETTY_PRINT));
        return self::SUCCESS;
    }
}
