<?php
namespace App\Services\PlanV2;

use Carbon\Carbon;

class PlanEvaluator
{
    public function parseEntryZone(array $planItem): ?array
    {
        $entry = $planItem['entry'] ?? null;
        if (!is_array($entry)) return null;

        $low = $entry['zone_low'] ?? null;
        $high = $entry['zone_high'] ?? null;

        if (!is_numeric($low) || !is_numeric($high)) return null;

        return ['low' => (float)$low, 'high' => (float)$high];
    }

    public function isEntryValid(array $planItem, float $lastPrice): bool
    {
        $z = $this->parseEntryZone($planItem);
        if (!$z) return false;

        $validUntil = $planItem['entry']['valid_until'] ?? null;
        if ($validUntil) {
            try {
                if (Carbon::now()->gt(Carbon::parse($validUntil))) return false;
            } catch (\Throwable $e) {}
        }

        return $lastPrice >= $z['low'] && $lastPrice <= $z['high'];
    }

    public function staleReason(array $planItem, float $lastPrice, float $maxPctAway = 15.0): ?string
    {
        $z = $this->parseEntryZone($planItem);
        if (!$z) return 'Missing numeric entry zone.';

        $mid = ($z['low'] + $z['high']) / 2.0;
        if ($mid <= 0) return null;

        $pctAway = abs($lastPrice - $mid) / $mid * 100.0;
        if ($pctAway > $maxPctAway) {
            return sprintf('Price %.2f is %.1f%% away from entry mid %.2f.', $lastPrice, $pctAway, $mid);
        }
        return null;
    }

    public function checkExit(array $planItem, string $side, float $lastPrice): array
    {
        $exit = $planItem['exit_plan'] ?? [];
        $stop = $exit['stop_loss'] ?? null;
        $inv  = $exit['invalidation'] ?? null;
        $targets = $exit['targets'] ?? [];

        $side = strtoupper($side);

        // Targets: take first hit
        $targetHit = null;
        foreach ($targets as $t) {
            if (!is_array($t) || !isset($t['price'])) continue;
            $tp = (float)$t['price'];
            if ($side === 'LONG' && $lastPrice >= $tp) { $targetHit = $tp; break; }
            if ($side === 'SHORT' && $lastPrice <= $tp) { $targetHit = $tp; break; }
        }

        if ($side === 'LONG') {
            if (is_numeric($stop) && $lastPrice <= (float)$stop) return ['close' => true, 'code' => 'STOP_HIT', 'text' => "Stop loss hit @ {$stop}"];
            if (is_numeric($inv)  && $lastPrice <= (float)$inv)  return ['close' => true, 'code' => 'INVALIDATION', 'text' => "Invalidation hit @ {$inv}"];
            if ($targetHit !== null) return ['close' => true, 'code' => 'TARGET_HIT', 'text' => "Target hit @ {$targetHit}"];
        } else {
            if (is_numeric($stop) && $lastPrice >= (float)$stop) return ['close' => true, 'code' => 'STOP_HIT', 'text' => "Stop loss hit @ {$stop}"];
            if (is_numeric($inv)  && $lastPrice >= (float)$inv)  return ['close' => true, 'code' => 'INVALIDATION', 'text' => "Invalidation hit @ {$inv}"];
            if ($targetHit !== null) return ['close' => true, 'code' => 'TARGET_HIT', 'text' => "Target hit @ {$targetHit}"];
        }

        return ['close' => false, 'code' => null, 'text' => null];
    }
}
