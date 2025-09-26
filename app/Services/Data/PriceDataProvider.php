<?php

namespace App\Services\Data;

use Illuminate\Support\Facades\DB;
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

        $row = DB::table($tDaily)
            ->select($c['close'].' as close')
            ->where($c['ticker'], $ticker)
            ->where($c['date'], $prevDay)
            ->first();

        return $row ? (float)$row->close : null;
    }

    public function getBarsInWindow(string $ticker, CarbonImmutable $startEt, CarbonImmutable $endEt): array
    {
        $tIntra = $this->cfg['tables']['intraday'];
        $c = $this->cfg['cols'];

        $startUtc = $startEt->tz('UTC')->toDateTimeString();
        $endUtc   = $endEt->tz('UTC')->toDateTimeString();

        $rows = DB::table($tIntra)
            ->where($c['ticker'], $ticker)
            ->whereBetween($c['ts'], [$startUtc, $endUtc])
            ->orderBy($c['ts'])
            ->get([$c['ts'].' as ts', $c['open'].' as o', $c['high'].' as h', $c['low'].' as l', $c['last'].' as p', $c['volume'].' as v']);

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
    }
}
