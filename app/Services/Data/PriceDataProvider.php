<?php

namespace App\Services\Data;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\CarbonImmutable;

class PriceDataProvider
{
    public function __construct(private array $cfg = []) {
        $this->cfg = $this->cfg ?: config('nyopen_data');
    }

    public function getPrevClose(string $ticker, CarbonImmutable $dateEt): ?float
    {
        $tDaily = $this->cfg['tables']['daily'];
        $c = $this->cfg['cols'];
        $prevDay = $dateEt->subDay()->format('Y-m-d');

        Log::info('NYOPEN data.prev.query', [
            'table'   => $tDaily,
            'ticker'  => $ticker,
            'prevDay' => $prevDay,
            'col_ticker'=>$c['ticker'],'col_date'=>$c['date'],'col_close'=>$c['close'],
        ]);

        try {
            $row = DB::table($tDaily)
                ->select($c['close'].' as close')
                ->where($c['ticker'], $ticker)
                ->where($c['date'], $prevDay)
                ->first();

            Log::info('NYOPEN data.prev.result', [
                'has_prev' => $row ? true : false,
                'val'      => $row->close ?? null
            ]);

            return $row ? (float)$row->close : null;
        } catch (\Throwable $e) {
            Log::error('NYOPEN data.prev.error', ['msg'=>$e->getMessage()]);
            return null;
        }
    }

    public function getBarsInWindow(string $ticker, CarbonImmutable $startEt, CarbonImmutable $endEt): array
    {
        $tIntra = $this->cfg['tables']['intraday'];
        $c = $this->cfg['cols'];

        $startUtc = $startEt->tz('UTC')->toDateTimeString();
        $endUtc   = $endEt->tz('UTC')->toDateTimeString();

        Log::info('NYOPEN data.bars.query', [
            'table'    => $tIntra,
            'ticker'   => $ticker,
            'startUtc' => $startUtc,
            'endUtc'   => $endUtc,
            'col_ts'=>$c['ts'],'col_open'=>$c['open'],'col_high'=>$c['high'],'col_low'=>$c['low'],'col_last'=>$c['last'],'col_vol'=>$c['volume'],
        ]);

        try {
            $rows = DB::table($tIntra)
                ->where($c['ticker'], $ticker)
                ->whereBetween($c['ts'], [$startUtc, $endUtc])
                ->orderBy($c['ts'])
                ->get([$c['ts'].' as ts', $c['open'].' as o', $c['high'].' as h', $c['low'].' as l', $c['last'].' as p', $c['volume'].' as v']);

            Log::info('NYOPEN data.bars.result', ['rows' => count($rows)]);

            $bars = [];
            foreach ($rows as $r) {
                $bars[] = [
                    'ts_utc' => $r->ts,
                    'open'   => $r->o ?? null,
                    'high'   => $r->h ?? null,
                    'low'    => $r->l ?? null,
                    'price'  => $r->p ?? null,
                    'volume' => $r->v ?? null,
                ];
            }
            return ['count' => count($bars), 'bars' => $bars];
        } catch (\Throwable $e) {
            Log::error('NYOPEN data.bars.error', ['msg'=>$e->getMessage()]);
            return ['count'=>0,'bars'=>[]];
        }
    }
}
