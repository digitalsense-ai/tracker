<?php

namespace App\Enums\AI;

enum MarketRegime: string
{
    case Trend = 'trend';
    case Range = 'range';
    case Expansion = 'expansion';
    case Compression = 'compression';
    case RiskOn = 'risk_on';
    case RiskOff = 'risk_off';
    case NewsDriven = 'news_driven';
    case Unknown = 'unknown';
}
