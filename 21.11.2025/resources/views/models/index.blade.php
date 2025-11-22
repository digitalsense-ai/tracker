@extends('layouts.app')
@section('title','Models')
@section('header_title','Models')

@section('content')
  <section class="grid" style="grid-template-columns:repeat(1,1fr);gap:16px">
    <div class="card" style="padding:12px">
      <div style="display:flex;justify-content:space-between;align-items:center;">
        <div class="bold">All Models</div>
        <a href="{{ route('models.create') }}">+ New model</a>
      </div>
    </div>

    @forelse($models as $m)
      <article class="card" style="padding:16px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px">
          <div>
            <div class="bold">{{ $m->name }}</div>
            <div class="small">Wallet: <span class="mono">{{ $m->wallet ?: '#' }}</span></div>
            <div class="small">Check every {{ $m->check_interval_min }} min</div>
          </div>
          <div style="text-align:right">
            <div class="{{ ($m->return_pct ?? 0) >= 0 ? 'good' : 'bad' }} bold">
              {{ ($m->return_pct >= 0 ? '+' : '') . number_format($m->return_pct ?? 0,2) }}%
            </div>
            <div class="small">${{ number_format($m->equity ?? 0,2) }}</div>
          </div>
        </div>

        <div style="display:flex;gap:8px;margin-top:10px">
          <a href="{{ route('models.show',$m->slug) }}">View</a>
          <a href="{{ route('models.edit',$m) }}">Edit</a>
        </div>
      </article>
    @empty
      <div class="card" style="padding:16px">No models yet.</div>
    @endforelse
  </section>
@endsection
