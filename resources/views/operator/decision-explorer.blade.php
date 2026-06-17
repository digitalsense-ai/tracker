<div class="min-h-screen bg-slate-950 text-slate-100 p-10">
    <div class="max-w-7xl mx-auto space-y-8">
        <header>
            <div class="text-xs uppercase tracking-widest text-cyan-400">Decision Explorer</div>
            <h1 class="mt-3 text-5xl font-bold">Adaptive Decision Investigation</h1>
            <p class="mt-4 max-w-4xl text-slate-400">Human-readable adaptive reasoning, governance visibility, drift visibility, and historical timeline analysis.</p>
        </header>

        <section class="grid grid-cols-4 gap-6">
            <article class="rounded-3xl bg-slate-900 p-6 border border-slate-800">
                <div class="text-xs uppercase text-slate-400">Decision</div>
                <div class="mt-3 text-3xl font-bold">NVDA ORB</div>
            </article>

            <article class="rounded-3xl bg-slate-900 p-6 border border-slate-800">
                <div class="text-xs uppercase text-slate-400">Confidence</div>
                <div class="mt-3 text-3xl font-bold text-emerald-400">87%</div>
            </article>

            <article class="rounded-3xl bg-slate-900 p-6 border border-slate-800">
                <div class="text-xs uppercase text-slate-400">Governance</div>
                <div class="mt-3 text-3xl font-bold">Approved</div>
            </article>

            <article class="rounded-3xl bg-slate-900 p-6 border border-slate-800">
                <div class="text-xs uppercase text-slate-400">Drift State</div>
                <div class="mt-3 text-3xl font-bold">Stable</div>
            </article>
        </section>

        <div class="grid grid-cols-[1.4fr_1fr] gap-8">
            <section class="rounded-[2rem] border border-slate-800 bg-slate-900 p-8">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold">Decision Timeline</h2>
                    <button class="rounded-2xl bg-cyan-500 px-5 py-3 font-semibold text-black">Open Timeline Explorer</button>
                </div>

                <div class="mt-8 space-y-6">
                    @foreach([
                        '08:42 Premarket scanner detected ORB candidate',
                        '08:44 Confidence increased to 87%',
                        '08:45 Governance approved setup',
                        '08:47 Drift remained stable',
                        '08:48 Trade candidate finalized',
                    ] as $event)
                        <div class="rounded-2xl bg-slate-800 p-5 text-slate-200">
                            {{ $event }}
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="space-y-6">
                <article class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6">
                    <h3 class="text-xl font-bold">Reason Codes</h3>

                    <div class="mt-5 flex flex-wrap gap-3">
                        @foreach([
                            'Trend Confirmed',
                            'Relative Volume Elevated',
                            'Sector Momentum Aligned',
                            'Market Regime Supportive',
                        ] as $reason)
                            <span class="rounded-xl bg-slate-800 px-4 py-3 text-sm">{{ $reason }}</span>
                        @endforeach
                    </div>
                </article>

                <article class="rounded-[2rem] border border-orange-400/20 bg-orange-500/10 p-6">
                    <h3 class="text-xl font-bold">Warnings</h3>
                    <p class="mt-4 text-slate-300">Increased volatility detected during execution window.</p>
                </article>

                <article class="rounded-[2rem] border border-slate-800 bg-slate-900 p-6">
                    <h3 class="text-xl font-bold">Simulation Snapshot</h3>

                    <div class="mt-5 space-y-4">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Historical Win Rate</span>
                            <span class="font-semibold">71%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Expected RR</span>
                            <span class="font-semibold">2.3</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Execution Quality</span>
                            <span class="font-semibold text-emerald-400">Strong</span>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </div>
</div>
