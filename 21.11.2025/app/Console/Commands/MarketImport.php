<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class MarketImport extends Command
{
    protected $signature = 'market:import {--daily=} {--intraday=} {--truncate}';
    protected $description = 'Import market data into prices_daily / prices_intraday';

    public function handle()
    {
        if ($this->option('truncate')) {
            if ($this->option('daily')) DB::table('prices_daily')->truncate();
            if ($this->option('intraday')) DB::table('prices_intraday')->truncate();
        }
        if ($path = $this->option('daily')) $this->importDaily($path);
        if ($path = $this->option('intraday')) $this->importIntraday($path);
    }

    protected function importDaily($path)
    {
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        foreach ($csv->getRecords() as $row) {
            DB::table('prices_daily')->insert([
                'ticker'=>$row['ticker'],
                'date'=>$row['date'],
                'close'=>$row['close'],
            ]);
        }
        $this->info('Daily imported.');
    }

    protected function importIntraday($path)
    {
        $csv = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);
        foreach ($csv->getRecords() as $row) {
            DB::table('prices_intraday')->insert([
                'ticker'=>$row['ticker'],
                'ts'=>$row['ts'],
                'open'=>$row['open'],
                'high'=>$row['high'],
                'low'=>$row['low'],
                'last'=>$row['last'],
                'volume'=>$row['volume'],
            ]);
        }
        $this->info('Intraday imported.');
    }
}
