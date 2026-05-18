<?php

namespace App\Enums\AI;

enum ThesisState: string
{
    case Discovery = 'discovery';
    case Validation = 'validation';
    case Expansion = 'expansion';
    case Continuation = 'continuation';
    case Compression = 'compression';
    case Exhaustion = 'exhaustion';
    case Exit = 'exit';
    case Unknown = 'unknown';
}
