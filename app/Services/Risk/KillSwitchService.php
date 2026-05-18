<?php

namespace App\Services\Risk;

class KillSwitchService
{
    /**
     * Foundation kill switch service.
     *
     * Future versions may support:
     * - system-level kill switch
     * - model-level kill switch
     * - strategy-level kill switch
     * - symbol-level kill switch
     * - broker/execution kill switch
     *
     * Initial version is informational only.
     */
    public function isActive(string $scope = 'system'): bool
    {
        return false;
    }
}
