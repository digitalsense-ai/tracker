<div class="min-h-screen bg-slate-950 text-slate-100 flex">
    <aside class="w-72 border-r border-slate-800 bg-slate-900 p-6">
        <div class="text-xs uppercase tracking-widest text-cyan-400">Adaptive Intelligence</div>
        <h1 class="mt-3 text-3xl font-bold">Mission Control</h1>
        <p class="mt-2 text-sm text-slate-400">Institutional adaptive intelligence operating platform.</p>

        <nav class="mt-8 space-y-2">
            @foreach([
                'Mission Control',
                'Portfolio Intelligence',
                'Decision Explorer',
                'Governance Center',
                'Drift Center',
                'Timeline Explorer',
                'Research Lab',
                'Incidents',
                'Providers',
                'Agents',
                'Settings',
            ] as $item)
                <div class="rounded-2xl px-4 py-3 {{ $item === 'Mission Control' ? 'bg-slate-800' : 'text-slate-400' }}">
                    {{ $item }}
                </div>
            @endforeach
        </nav>
    </aside>

    <main class="flex-1">
        <header class="border-b border-slate-800 bg-slate-900 p-6">
            <div class="grid grid-cols-6 gap-4">
                @foreach([
                    'Market Regime' => 'Bullish Expansion',
                    'System Health' => '94%',
                    'Governance' => 'Stable',
                    'Portfolio Heat' => 'Moderate',
                    'Execution Health' => 'Healthy',
                    'AI Confidence' => '82%',
                ] as $label => $value)
                    <section class="rounded-3xl bg-slate-800 p-4">
                        <div class="text-xs uppercase tracking-wide text-slate-400">{{ $label }}</div>
                        <div class="mt-2 text-xl font-bold">{{ $value }}</div>
                    </section>
                @endforeach
            </div>
        </header>

        <div class="grid grid-cols-[1fr_420px] gap-0">
            <section class="p-8 space-y-8">
                <div>
                    <div class="text-xs uppercase tracking-widest text-cyan-400">Adaptive Intelligence Feed</div>
                    <h2 class="mt-3 text-4xl font-bold">Live Institutional Oversight</h2>
                    <p class="mt-3 max-w-3xl text-slate-400">Monitor opportunities, governance events, drift behavior, incidents, and adaptive intelligence from one surface.</p>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    @foreach([
                        ['symbol' => 'NVDA', 'setup' => 'ORB Breakout Candidate', 'confidence' => '87%', 'governance' => 'Approved', 'drift' => 'Stable'],
                        ['symbol' => 'MSFT', 'setup' => 'Institutional Pullback', 'confidence' => '84%', 'governance' => 'Approved', 'drift' => 'Low'],
                        ['symbol' => 'TSLA', 'setup' => 'Momentum Retest', 'confidence' => '79%', 'governance' => 'Review', 'drift' => 'Moderate'],
                    ] as $candidate)
                        <article class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6">
                            <div class="flex justify-between gap-4">
                                <div>
                                    <div class="text-4xl font-bold">{{ $candidate['symbol'] }}</div>
                                    <div class="mt-2 text-slate-400">{{ $candidate['setup'] }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs uppercase text-slate-400">Confidence</div>
                                    <div class="mt-2 text-3xl font-bold text-emerald-400">{{ $candidate['confidence'] }}</div>
                                </div>
                            </div>

                            <div class="mt-6 grid grid-cols-2 gap-4">
                                <div class="rounded-2xl bg-slate-800 p-4">
                                    <div class="text-xs uppercase text-slate-400">Governance</div>
                                    <div class="mt-2 font-semibold">{{ $candidate['governance'] }}</div>
                                </div>
                                <div class="rounded-2xl bg-slate-800 p-4">
                                    <div class="text-xs uppercase text-slate-400">Drift</div>
                                    <div class="mt-2 font-semibold">{{ $candidate['drift'] }}</div>
                                </div>
                            </div>

                            <div class="mt-6 flex gap-3">
                                <button class="rounded-2xl bg-cyan-500 px-4 py-3 font-semibold text-black">Review</button>
                                <button class="rounded-2xl bg-slate-800 px-4 py-3">Timeline</button>
                                <button class="rounded-2xl bg-slate-800 px-4 py-3">Simulate</button>
                            </div>
                        </article>
                    @endforeach
                </div>

                <section class="rounded-[2rem] border border-orange-400/20 bg-orange-500/10 p-6">
                    <div class="flex items-center justify-between gap-6">
                        <div>
                            <div class="text-xl font-bold">Execution Drift Rising</div>
                            <p class="mt-2 text-slate-300">Execution quality degradation detected across 3 active strategies.</p>
                        </div>
                        <button class="rounded-2xl bg-cyan-500 px-5 py-3 font-semibold text-black">Investigate</button>
                    </div>
                </section>
            </section>

            <aside class="border-l border-slate-800 bg-slate-900 p-6">
                <div class="text-xs uppercase tracking-widest text-cyan-400">Workflow Queue</div>
                <h2 class="mt-3 text-3xl font-bold">Operator Coordination</h2>
                <p class="mt-3 text-slate-400">Review adaptive recommendations, escalations, and simulations.</p>

                <div class="mt-8 space-y-4">
                    @foreach([
                        ['title' => 'Governance Review Required', 'priority' => 'Elevated'],
                        ['title' => 'Adaptive Simulation Review', 'priority' => 'Info'],
                        ['title' => 'Provider Health Check', 'priority' => 'Warning'],
                    ] as $task)
                        <article class="rounded-3xl bg-slate-800 p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div class="font-semibold">{{ $task['title'] }}</div>
                                <span class="rounded-full bg-slate-700 px-3 py-1 text-sm">{{ $task['priority'] }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            </aside>
        </div>
    </main>
</div>
