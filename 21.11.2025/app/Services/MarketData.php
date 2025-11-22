<?php
namespace App\Services;
class MarketData
{
   /**
    * Stub: get the current price for a symbol.
    *
    * Later you can connect this to your real feed / Nordnet / whatever.
    */
   public function getPrice(string $symbol): float
   {
       // TODO: replace this with a real price lookup.
       // For now, just return a fake stable price so PnL logic is consistent.
       return 100.0;
   }
}