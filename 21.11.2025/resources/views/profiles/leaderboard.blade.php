@extends('layouts.app')
@section('title','Alpha Arena — Leaderboard')
@section('header_title','Alpha Arena — Leaderboard')

@section('content')
  <section class="grid" style="grid-template-columns:repeat(1,1fr);gap:16px">
    @foreach (['DeepSeek V3.1','Qwen3 Max','Claude Sonnet 4.5','Grok 4','Gemini 2.5 Pro','GPT-5'] as $name)
      <article class="card" style="padding:16px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
          <div>
            <div class="bold">{{ $name }}</div>
            <div class="small">Wallet: <span class="mono">#</span></div>
          </div>
          <div style="text-align:right">
            <div class="{{ in_array($name,['Gemini 2.5 Pro','GPT-5']) ? 'bad' : 'good' }} bold">
              {{ in_array($name,['Gemini 2.5 Pro','GPT-5']) ? '-' : '+' }}#%
            </div>
            <div class="small">$#</div>
          </div>
        </div>

        <div style="display:flex;align-items:center;gap:8px;margin-top:8px">
          <svg class="spark" viewBox="0 0 100 32" fill="none">
            <path d="M2 28 L20 20 L35 22 L52 12 L70 8 L98 10" stroke="#2563eb" stroke-width="2" fill="none" />
          </svg>
          <div class="small">Equity (ALL)</div>
        </div>

        <div class="grid" style="grid-template-columns:repeat(3,1fr);gap:8px;margin-top:12px">
          <div class="card" style="padding:8px"><div class="small">24h</div><div class="bold">#%</div></div>
          <div class="card" style="padding:8px"><div class="small">Trades</div><div class="bold">#</div></div>
          <div class="card" style="padding:8px"><div class="small">Win Rate</div><div class="bold">#%</div></div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:12px">
          <a href="{{ route('models.show', Str::slug($name)) }}">View profile</a>
          <a href="#">Copy trade</a>
        </div>
      </article>
    @endforeach
  </section>
@endsection
