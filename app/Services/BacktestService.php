<?php

namespace App\Services;

use Carbon\Carbon;

class BacktestService
{
    /**
     * Adapter-metode: prøver smidigt at kalde din eksisterende simulate()
     * uanset om den forventer (int $days), ($days, array $options),
     * ($from, $to, array $options), ($from, $to) eller bare (array $options).
     * Fanger exceptions og prøver næste signatur.
     */
    public function simulateForDate(Carbon $startDate, int $days = 1, array $options = [])
    {
        $days = max(1, (int) $days);
        $from = $startDate->copy()->startOfDay();
        $to   = $startDate->copy()->addDays($days)->endOfDay();

        $optWithDates = array_merge($options, [
            'date_from' => $from,
            'date_to'   => $to,
        ]);

        if (!method_exists($this, 'simulate')) {
            throw new \BadMethodCallException('simulate() findes ikke i BacktestService.');
        }

        // Prøv flere typiske signaturer i faldende prioritet
        $attempts = [
            function () use ($days, $optWithDates) { return $this->simulate($days, $optWithDates); },
            function () use ($days) { return $this->simulate($days); },
            function () use ($from, $to, $options) { return $this->simulate($from, $to, $options); },
            function () use ($from, $to) { return $this->simulate($from, $to); },
            function () use ($optWithDates) { return $this->simulate($optWithDates); },
            function () { return $this->simulate(); },
        ];

        $errors = [];
        foreach ($attempts as $i => $call) {
            try {
                return $call();
            } catch (\ArgumentCountError $e) {
                $errors.append('Attempt #' . ($i+1) . ' ArgumentCountError: ' . $e->getMessage());
                continue;
            } catch (\TypeError $e) {
                $errors.append('Attempt #' . ($i+1) . ' TypeError: ' . $e->getMessage());
                continue;
            } catch (\Throwable $e) {
                // Hvis selve simulate() kører men fejler internt, så bobler vi op.
                // Det tyder på, at signaturen var korrekt, men noget andet gik galt.
                throw $e;
            }
        }

        // Hvis ingen forsøg lykkedes, giv en tydelig fejl
        $hint = implode(" | ", $errors);
        throw new \BadMethodCallException('Kunne ikke kalde simulate() med nogen kendt signatur. Forsøg: ' . $hint);
    }
}
