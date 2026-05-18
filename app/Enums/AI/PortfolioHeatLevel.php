<?php

namespace App\Enums\AI;

enum PortfolioHeatLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';
}
