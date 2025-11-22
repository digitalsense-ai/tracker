<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\StrategyProfile;

class StrategyProfilesSeeder extends Seeder {
    public function run(): void {
        if (StrategyProfile::count()>0) return;
        $session_starts=['09:30','09:45'];$session_ends=['10:30','10:45'];$force_exits=['11:15','11:30'];
        $ema_pairs=[[20,50],[21,55]];$use_vwap=[true,false];$atr_min=[0.20,0.25,0.30,0.35];$gap_max=[2.0,3.0];
        $buffer=[0.01,0.02,0.05];$depth_spans=[[0.30,0.60],[0.25,0.50]];$rvol_min=[1.3,1.5,1.8];
        $tp1_r=[0.8,1.0];$tp2_r=[1.8,2.0,3.0];$trailing=[false,true];
        $position=[3000,5000];$fees_bps=[8,10];$min_fee=[2.0];
        for($i=0;$i<128;$i++){
            $s=[
              'session'=>['start'=>$session_starts[array_rand($session_starts)],'end'=>$session_ends[array_rand($session_ends)],'force_exit'=>$force_exits[array_rand($force_exits)]],
              'risk'=>['position_size'=>$position[array_rand($position)],'fees_bps'=>$fees_bps[array_rand($fees_bps)],'min_fee'=>$min_fee[0]],
              'filters'=>['one_trade_per_ticker'=>true,'ema_fast'=>$ema_pairs[array_rand($ema_pairs)][0],'ema_slow'=>$ema_pairs[array_rand($ema_pairs)][1],'use_vwap'=>$use_vwap[array_rand($use_vwap)],'atr5m_min_pct'=>$atr_min[array_rand($atr_min)],'gap_pct_max'=>$gap_max[array_rand($gap_max)],'skip_extreme_orb_factor'=>1.8],
              'retest'=>['buffer_pct'=>$buffer[array_rand($buffer)],'depth_min'=>$depth_spans[array_rand($depth_spans)][0],'depth_max'=>$depth_spans[array_rand($depth_spans)][1],'rvol_min'=>$rvol_min[array_rand($rvol_min)]],
              'management'=>['tp1_r'=>$tp1_r[array_rand($tp1_r)],'tp2_r'=>$tp2_r[array_rand($tp2_r)],'use_trailing'=>$trailing[array_rand($trailing)],'trailing_mult'=>1.5,'cooldown_after_sl'=>true]
            ];
            StrategyProfile::create(['name'=>'P'.str_pad($i+1,3,'0',STR_PAD_LEFT),'description'=>'Auto-seeded','settings'=>$s,'enabled'=>true,'rank'=>0]);
        }
    }
}