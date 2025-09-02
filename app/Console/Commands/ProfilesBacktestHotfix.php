<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StrategyProfile;
use App\Models\ProfileResult;
use App\Services\BacktestService;
use Carbon\Carbon;
use Throwable;

class ProfilesBacktestHotfix extends Command
{
    protected $signature = 'profiles:backtest-v2 {--days=10} {--limit=0} {--profile=} {--vv}';
    protected $description = 'Batch backtest with robust BacktestService fallbacks + logging';

    public function __construct(protected BacktestService $svc){ parent::__construct(); }

    public function handle(): int
    {
        $days=(int)($this->option('days') ?: 10);
        $limit=(int)($this->option('limit') ?: 0);
        $only=$this->option('profile');
        $verbose=(bool)$this->option('vv');

        $q=StrategyProfile::where('enabled',true)->orderBy('id');
        if($only){ $q->where('id',$only); }
        elseif($limit>0){ $q->limit($limit); }
        $profiles=$q->get();
        if($profiles->isEmpty()){ $this->warn('No profiles to run.'); return self::SUCCESS; }

        $start=Carbon::now('Europe/Copenhagen')->subDays($days)->startOfDay();
        $window=$start->toDateString().'..'.Carbon::now('Europe/Copenhagen')->toDateString();

        $bar=$this->output->createProgressBar($profiles->count()); $bar->start();

        foreach($profiles as $p){
            try{
                $res=$this->runService($start,$days,$p->settings,$verbose);
                $metrics=$this->computeMetrics($res);
                $metrics['window']=$window;
                if($verbose){
                    $this->line(sprintf("\n#%d %s → trades=%d win%%=%.1f net=%.2f pf=%.2f",
                        $p->id,$p->name,$metrics['trades'],$metrics['winrate'],$metrics['net_pl'],$metrics['profit_factor']));
                }
                ProfileResult::create([
                    'strategy_profile_id'=>$p->id,
                    'window'=>$window,
                    'trades'=>$metrics['trades'],
                    'winrate'=>$metrics['winrate'],
                    'avg_r'=>$metrics['avg_r'],
                    'net_pl'=>$metrics['net_pl'],
                    'profit_factor'=>$metrics['profit_factor'],
                    'drawdown_pct'=>$metrics['drawdown_pct'],
                    'score'=>$this->score($metrics),
                    'metrics'=>$metrics,
                ]);
            }catch(Throwable $e){
                $msg=$e->getMessage();
                if($verbose){ $this->error("Error profile #{$p->id}: $msg"); }
                ProfileResult::create([
                    'strategy_profile_id'=>$p->id,
                    'window'=>$window,
                    'trades'=>0,'winrate'=>0,'avg_r'=>0,'net_pl'=>0,'profit_factor'=>0,'drawdown_pct'=>0,'score'=>0,
                    'metrics'=>['error'=>$msg],
                ]);
            }
            $bar->advance();
        }
        $bar->finish(); $this->newLine(); $this->info('Done.');
        return self::SUCCESS;
    }

    protected function runService($start,$days,array $settings,bool $verbose=false){
        try{ return $this->svc->simulateForDate($start,$days,['profile_settings'=>$settings]); }
        catch(\Throwable $e){ if($verbose){ $this->warn('simulateForDate failed: '.$e->getMessage()); } }
        try{ return $this->svc->simulate($days,['profile_settings'=>$settings,'start'=>$start]); }
        catch(\Throwable $e){ if($verbose){ $this->warn('simulate failed: '.$e->getMessage()); } }
        try{ $end=(clone $start)->addDays($days); return $this->svc->simulateRange($start,$end,['profile_settings'=>$settings]); }
        catch(\Throwable $e){ if($verbose){ $this->warn('simulateRange failed: '.$e->getMessage()); } }
        throw new \RuntimeException('No compatible simulate* method found in BacktestService.');
    }

    protected function computeMetrics($res): array
    {
        $trades = is_array($res) ? ($res['trades'] ?? $res) : [];
        $n=0;$wins=0;$net=0;$gp=0;$gn=0;$r_sum=0;$r_cnt=0;$eq=0;$pk=0;$dd=0;
        foreach($trades as $t){
            $n++; $p=(float)($t['net'] ?? ($t['pnl'] ?? 0)); $r=$t['R'] ?? ($t['r'] ?? null);
            if($r!==null){ $r_sum+=(float)$r; $r_cnt++; }
            $net+=$p; if($p>=0){$wins++;$gp+=$p;} else {$gn+=abs($p);}
            $eq+=$p; $pk=max($pk,$eq); $dd=max($dd,$pk>0?($pk-$eq)/$pk:0);
        }
        $pf=$gn>0?$gp/$gn:($gp>0?999:0);
        return ['trades'=>$n,'winrate'=>$n?($wins/$n)*100:0,'avg_r'=>$r_cnt?$r_sum/$r_cnt:0,'net_pl'=>$net,'profit_factor'=>$pf,'drawdown_pct'=>$dd*100];
    }

    protected function score(array $m): float
    {
        $wr=max(0.0,min(100.0,(float)($m['winrate']??0)));
        $pf=max(0.0,(float)($m['profit_factor']??0));
        $dd=max(1.0,(float)($m['drawdown_pct']??0)+1.0);
        return ($wr*$pf)/$dd;
    }
}
