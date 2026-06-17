<div class="min-h-screen bg-slate-950 text-slate-100 p-10">
    <div class="max-w-7xl mx-auto space-y-8">
        <header>
            <div class="text-xs uppercase tracking-widest text-cyan-400">Governance Center</div>
            <h1 class="mt-3 text-5xl font-bold">Adaptive Governance Oversight</h1>
            <p class="mt-4 max-w-4xl text-slate-400">Monitor policy evaluations, escalations, safe-mode recommendations, and institutional governance workflows.</p>
        </header>

        <section class="grid grid-cols-4 gap-6">
            @foreach([
                ['label' => 'Governance State', 'value' => 'Stable'],
                ['label' => 'Safe Mode', 'value' => 'Inactive'],
                ['label' => 'Escalations', 'value' => '2 Active'],
                ['label' => 'Review Queue', 'value' => '5 Pending'],
            ] as $metric)
                <article class="rounded-3xl border border-slate-800 bg-slate-900 p-6">
                    <div class="text-xs uppercase text-slate-400">{{ $metric['label'] }}</div>
                    <div class="mt-3 text-3xl font-bold">{{ $metric['value'] }}</div>
                </article>
            @endforeach
        </section>

        <section class="space-y-6">
            @foreach([
                ['title' => 'Execution Drift Escalation', 'severity' => 'Elevated'],
                ['title' => 'Provider Latency Governance Review', 'severity' => 'Warning'],
                ['title' => 'Adaptive Recommendation Review', 'severity' => 'Info'],
            ] as $incident)
                <article class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6 flex items-center justify-between">
                    <div>
                        <div class="text-2xl font-bold">{{ $incident['title'] }}</div>
                        <div class="mt-3 text-slate-400">Governance review and operator coordination required.</div>
                    </div>

                    <div class="flex items-center gap-4">
                        <span class="rounded-full bg-slate-800 px-4 py-2 text-sm">{{ $incident['severity'] }}</span>
                        <button class="rounded-2xl bg-cyan-500 px-5 py-3 font-semibold text-black">Review</button>
                    </div>
                </article>
            @endforeach
        </section>
    </div>
</div>
