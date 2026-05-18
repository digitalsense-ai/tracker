<?php

namespace App\Enums\AI;

enum ActivationMode: string
{
    case Disabled = 'disabled';
    case ObserveOnly = 'observe_only';
    case Assistive = 'assistive';
    case ControlledAutomation = 'controlled_automation';
}
