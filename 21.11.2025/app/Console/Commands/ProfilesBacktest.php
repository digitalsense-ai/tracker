<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\StrategyProfile;
use App\Models\ProfileResult;
use App\Services\BacktestService;
use Carbon\Carbon;
use Throwable;

class ProfilesBacktest extends Command {
    protected $signature='profiles:backtest {--days=10} {--limit=0}';
    protected $description='Run batch backtest for all enabled profiles';
    public function __construct(protected BacktestService $backtest){parent::__construct();}
    public function handle(): int {
        $days=(int)$this->option('days')?:10; $limit=(int)$this->option('limit')?:0;
        $q=StrategyProfile::where('enabled',true)->orderBy('id'); if($limit>0)$q->limit($limit);
        $profiles=$q->get(); if($profiles->isEmpty()){ $this->warn('No enabled profiles'); return self::SUCCESS; }
        $start=Carbon::now('Europe/Copenhagen')->subDays($days)->startOfDay();
        $window=$start->toDateString().'..'.Carbon::now('Europe/Copenhagen')->toDateString();
        $bar=$this->output->createProgressBar($profiles->count());$bar->start();
        foreach($profiles as $p){try{$res=$this->backtest->simulateForDate($start,$days,['profile_settings'=>$p->settings]);
          $m=$this->computeMetrics($res);ProfileResult::create(array_merge($m,['strategy_profile_id'=>$p->id,'window'=>$window,'metrics'=>$m]));}
          catch(Throwable $e){ProfileResult::create(['strategy_profile_id'=>$p->id,'window'=>$window,'metrics'=>['error'=>$e->getMessage()]]);} $bar->advance();}
        $bar->finish();$this->newLine();$this->info('Done.');return self::SUCCESS;}
    protected function computeMetrics($res): array { $trades=$res['trades']??[];$n=0;$wins=0;$net=0;$gp=0;$gn=0;$r_sum=0;$r_cnt=0;$eq=0;$pk=0;$dd=0;
      foreach($trades as $t){$n++;$p=(float)($t['net']??0);$r=$t['R']??null;if($r!==null){$r_sum+=$r;$r_cnt++;}$net+=$p;if($p>=0){$wins++;$gp+=$p;}else{$gn+=abs($p);} $eq+=$p;$pk=max($pk,$eq);$dd=max($dd,$pk>0?($pk-$eq)/$pk:0);} $pf=$gn>0?$gp/$gn:($gp>0?999:0);
      return ['trades'=>$n,'winrate'=>$n?($wins/$n)*100:0,'avg_r'=>$r_cnt?$r_sum/$r_cnt:0,'net_pl'=>$net,'profit_factor'=>$pf,'drawdown_pct'=>$dd*100,'score'=>($n?($wins/$n*100)*$pf/max(1,$dd*100+1):0)];}
}