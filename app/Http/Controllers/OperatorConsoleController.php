<?php

namespace App\Http\Controllers;

use App\Models\ConsensusDecision;
use App\Models\GovernanceEvent;
use App\Models\OperationalEvent;
use App\Models\Timeline;
use Illuminate\View\View;

class OperatorConsoleController extends Controller
{
    public function missionControl(): View
    {
        return $this->screen('Mission Control', 'Live institutional operating overview', 'mission-control');
    }

    public function decisionExplorer(): View
    {
        return $this->screen('Decision Explorer', 'Consensus and decision investigation', 'decision-explorer');
    }

    public function governanceCenter(): View
    {
        return $this->screen('Governance Center', 'Governance events, severity and review queue', 'governance-center');
    }

    public function timelineExplorer(): View
    {
        return $this->screen('Timeline Explorer', 'Operational timelines and event history', 'timeline-explorer');
    }

    public function driftCenter(): View
    {
        return $this->screen('Drift Center', 'Adaptive drift and calibration monitoring', 'drift-center');
    }

    public function researchLab(): View
    {
        return $this->screen('Research Lab', 'Historical analysis and controlled research workspace', 'research-lab');
    }

    public function incidentCenter(): View
    {
        return $this->screen('Incident Center', 'Operational incidents and escalation visibility', 'incident-center');
    }

    private function screen(string $title, string $subtitle, string $active): View
    {
        return view('operator.console', [
            'title' => $title,
            'subtitle' => $subtitle,
            'active' => $active,
            'metrics' => [
                'events' => OperationalEvent::query()->count(),
                'timelines' => Timeline::query()->count(),
                'consensus' => ConsensusDecision::query()->count(),
                'governance' => GovernanceEvent::query()->count(),
            ],
            'latestEvents' => OperationalEvent::query()->latest()->limit(10)->get(),
            'latestTimelines' => Timeline::query()->latest()->limit(10)->get(),
            'latestConsensus' => ConsensusDecision::query()->latest()->limit(10)->get(),
            'latestGovernance' => GovernanceEvent::query()->latest()->limit(10)->get(),
        ]);
    }
}
