<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestSampleController extends Controller
{
   public function index(Request $request)
   {
       $chartService = app(\App\Services\SaxoChartService::class);

       $bars = $chartService->getDailyBars(
                uic: 1249,
                assetType: 'STOCK',
                count: 30
            );
       usort($bars, fn($a,$b) => strcmp($a['date'], $b['date']));
     
       // --- ATR proxy (14)
      $trs = [];

      for ($i = 1; $i < count($bars); $i++) {
          $prev = $bars[$i - 1];
          $cur  = $bars[$i];

          $trs[] = max(
              $cur['high'] - $cur['low'],
              abs($cur['high'] - $prev['close']),
              abs($cur['low'] - $prev['close'])
          );
      }

      //$atr14   = collect($trs)->take(14)->avg();
      $atr14 = collect($trs)->slice(-14)->avg();
      $last    = last($bars)['close'];
      $atrPct  = $atr14 / $last;

      // --- Momentum (20)
      $close20 = $bars[count($bars) - 21]['close'];
      $momentum = abs($last / $close20 - 1);

      $ranges = collect($bars)
                //->take(20)
                ->slice(-20)
                ->map(fn ($b) => ($b['high'] - $b['low']) / $b['close']);
      $median = $ranges->median();
       dd($bars, $trs, $atr14, $last, $close20, $momentum, $ranges, $median);
   }   
}