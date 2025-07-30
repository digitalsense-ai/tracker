<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Stock;
use App\Models\Trade;

class SimulateTrades extends Command
{
    protected $signature = 'simulate:trades';
    protected $description = 'Simulate trades based on entry/exit logic';

    public function handle()
    {
        $candidates = Stock::whereIn('status', ['entry', 'exit'])->get();
        $created = 0;

        foreach ($candidates as $stock) {
            if (!Trade::where('ticker', $stock->ticker)->exists()) {
                $isWin = $stock->gap > 3 && $stock->rvol > 2;
                Trade::create([
                    'ticker' => $stock->ticker,
                    'entry_price' => $stock->price,
                    'exit_price' => $isWin ? $stock->price * 1.02 : $stock->price * 0.98,
                    'result' => $isWin ? 'win' : 'loss',
                ]);
                $created++;
            }
        }

        $this->info("Simulated {$created} trades.");
    }
}
