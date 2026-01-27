<?php
namespace App\Services;
use App\Models\SymbolMapping;
class SymbolUniverse {
 public function candidates(int $limit=20): array {
  return SymbolMapping::where('enabled_for_ai',true)
   ->orderBy('priority')
   ->limit($limit)
   ->pluck('symbol')
   ->toArray();
 }
}
