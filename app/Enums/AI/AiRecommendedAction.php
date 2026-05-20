<?php

namespace App\Enums\AI;

enum AiRecommendedAction: string
{
    case Continue = 'continue';
    case DowngradeCandidate = 'downgrade_candidate';
    case RemoveCandidate = 'remove_candidate';
    case PauseModel = 'pause_model';
    case ReduceRisk = 'reduce_risk';
    case ForceReplan = 'force_replan';
    case EmergencyStop = 'emergency_stop';
    case HoldTrade = 'hold_trade';
    case ExitTrade = 'exit_trade';
    case ScaleDown = 'scale_down';
    case ScaleUp = 'scale_up';
    case ReclassifyPosition = 'reclassify_position';
}
