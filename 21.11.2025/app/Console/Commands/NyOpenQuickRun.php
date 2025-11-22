<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use App\Services\NyOpenBacktester;

class NyOpenQuickRun extends Command
{
    protected $signature = 'nyopen:quick-run
        {--date= : YYYY-MM-DD eller "yesterday" (default) }
        {--universe= : Komma-separeret tickers }
        {--profiles= : Komma-separeret profilnavne (ellers alle enabled) }';

    protected $description = 'Synkron NY-open backtest (ingen queue); skriver trades og kører recompute bagefter med detaljeret logging.';

    public function handle()
    {
        $cfg = config('nyopen');

        $dateOpt = $this->option('date') ?: 'yesterday';
        if ($dateOpt === 'yesterday') {
            $date = CarbonImmutable::now($cfg['tz_app'])->subDay()->format('Y-m-d');
        } else {
            $date = CarbonImmutable::parse($dateOpt, $cfg['tz_app'])->format('Y-m-d');
        }

        $universe = $this->option('universe')
            ? array_filter(array_map('trim', explode(',', $this->option('universe'))))
            : $cfg['default_universe'];

        $profiles = $this->option('profiles')
            ? array_filter(array_map('trim', explode(',', $this->option('profiles'))))
            : DB::table('strategy_profiles')->where('enabled',1)->orderBy('name')->pluck('name')->all();

        $this->info('--- NYOPEN QUICK-RUN ---');
        $this->line('Date     : '.$date);
        $this->line('Universe : ['.implode(',', $universe).']');
        $this->line('Profiles : ['.implode(',', $profiles).']');

        // Reflection logging to show which class/file is actually loaded
        $svc = app(NyOpenBacktester::class);
        $svcFile = (new \ReflectionClass($svc))->getFileName();
        Log::info('NYOPEN service.class', ['class' => get_class($svc), 'file' => $svcFile]);

        Log::info('NYOPEN quick.start', ['date'=>$date,'universe'=>$universe,'profiles_count'=>count($profiles)]);

        $totalInserted = 0;

        foreach ($profiles as $pname) {
            $pid = DB::table('strategy_profiles')->where('name',$pname)->value('id');
            if (!$pid) { $this->warn("Profile not found: $pname"); Log::warning('NYOPEN profile.missing',['name'=>$pname]); continue; }

            $profileSum = 0;

            foreach ($universe as $ticker) {
                $startEt = CarbonImmutable::parse($date.' '.config('nyopen.window_start'), config('nyopen.tz_market'));
                $endEt   = CarbonImmutable::parse($date.' '.config('nyopen.window_end'),   config('nyopen.tz_market'));

                try {
                    $trades = $svc->runProfileOnTickerWindow($pid, $ticker, $startEt, $endEt);
                } catch (\Throwable $e) {
                    Log::error('NYOPEN svc.error', ['profile'=>$pname,'ticker'=>$ticker,'msg'=>$e->getMessage()]);
                    continue;
                }

                $n = is_countable($trades ?? []) ? count($trades) : 0;
                if ($n === 0) {
                    Log::warning('NYOPEN svc.zero', ['profile'=>$pname,'ticker'=>$ticker,'start'=>$startEt->toIso8601String(),'end'=>$endEt->toIso8601String()]);
                } else {
                    Log::info('NYOPEN svc.count', ['profile'=>$pname,'ticker'=>$ticker,'n'=>$n]);
                }

                foreach ($trades as $t) {
                    DB::table('trades')->updateOrInsert(
                        ['strategy_profile_id'=>$pid,'ticker'=>$ticker,'created_at'=>$t['created_at']],
                        [
                            'entry_price'=>$t['entry_price'],
                            'exit_price'=>$t['exit_price'],
                            'closed_at'=>$t['closed_at'] ?? null,
                            'forecast_type'=>$t['forecast_type'] ?? 'gap-up',
                            'market_session'=>'ny_open',
                            'updated_at'=>now('UTC'),
                        ]
                    );
                    $profileSum += 1;
                    $totalInserted += 1;
                }
            }

            Log::info('NYOPEN profile.sum', ['profile'=>$pname,'inserted'=>$profileSum]);
        }

        Log::info('NYOPEN quick.done', ['total_inserted'=>$totalInserted]);

        $this->line('Recomputing profile results...');
        Artisan::call('profiles:recompute', [
            '--days'=>0,'--table'=>'trades','--ts'=>'created_at','--auto-pnl'=>true,'-v'=>true,
        ]);
        $this->line(Artisan::output());

        $this->info('Done.');
        return 0;
    }
}
