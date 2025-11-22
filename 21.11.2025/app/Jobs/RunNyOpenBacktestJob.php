<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;
use App\Services\NyOpenBacktester;

class RunNyOpenBacktestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $dateYmd,
        protected array $universe,
        protected array $profiles,
        protected bool $dryRun = false
    ) {}

    public function handle(): void
    {
        $cfg = config('nyopen');
        $tzEt = $cfg['tz_market']; // America/New_York
        $windowStartEt = $cfg['window_start']; // 09:30:00
        $windowEndEt   = $cfg['window_end'];   // 11:30:00

        $service = app(NyOpenBacktester::class);

        $profilesToRun = $this->profiles;
        if (empty($profilesToRun)) {
            $profilesToRun = DB::table('strategy_profiles')
                ->where('enabled', 1)
                ->orderBy('name')
                ->pluck('name')
                ->all();
        }

        Log::info('NYOPEN job.start', ['date'=>$this->dateYmd,'profiles_count'=>count($profilesToRun),'universe'=>$this->universe]);

        $totalInserted = 0;

        foreach ($profilesToRun as $pname) {
            $pid = DB::table('strategy_profiles')->where('name', $pname)->value('id');
            if (!$pid) { Log::warning("NYOPEN job.profile_missing", ['name'=>$pname]); continue; }

            $profileSum = 0;

            foreach ($this->universe as $ticker) {
                $startEt = CarbonImmutable::parse($this->dateYmd . ' ' . $windowStartEt, $tzEt);
                $endEt   = CarbonImmutable::parse($this->dateYmd . ' ' . $windowEndEt,   $tzEt);

                try {
                    $trades = $service->runProfileOnTickerWindow($pid, $ticker, $startEt, $endEt);
                } catch (\Throwable $e) {
                    Log::error('NYOPEN job.svc_error', ['profile'=>$pname,'ticker'=>$ticker,'msg'=>$e->getMessage()]);
                    continue;
                }

                $n = is_countable($trades ?? []) ? count($trades) : 0;
                if ($n === 0) {
                    Log::warning('NYOPEN job.svc_zero', ['profile'=>$pname,'ticker'=>$ticker,'start'=>$startEt->toIso8601String(),'end'=>$endEt->toIso8601String()]);
                } else {
                    Log::info('NYOPEN job.svc_count', ['profile'=>$pname,'ticker'=>$ticker,'n'=>$n]);
                }

                if ($this->dryRun) { continue; }

                foreach ($trades as $t) {
                    DB::table('trades')->updateOrInsert(
                        ['strategy_profile_id' => $pid, 'ticker' => $ticker, 'created_at' => $t['created_at']],
                        [
                            'entry_price' => $t['entry_price'],
                            'exit_price'  => $t['exit_price'],
                            'closed_at'   => $t['closed_at'] ?? null,
                            'forecast_type' => $t['forecast_type'] ?? 'gap-up',
                            'market_session' => 'ny_open',
                            'updated_at' => now('UTC'),
                        ]
                    );
                    $profileSum += 1;
                    $totalInserted += 1;
                }
            }

            Log::info('NYOPEN job.profile_sum', ['profile'=>$pname,'inserted'=>$profileSum]);
        }

        Log::info('NYOPEN job.done', ['total_inserted'=>$totalInserted]);
    }
}
