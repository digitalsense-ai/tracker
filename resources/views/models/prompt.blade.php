@extends('layouts.app')
@section('title','Models')
@section('header_title','Models')

@section('content')
@php use Illuminate\Support\Str; @endphp
<link rel="stylesheet" href="/css/ai.css">

<div class="nav-tabs">
  @foreach($models as $model)
    <a href="{{ route('models.show', $model->slug) }}" class="{{ ($model->slug == $m->slug) ? 'active' : '' }}">{{ $model->name }}</a>
  @endforeach
</div>

<div class="nav-tabs">
  <a href="{{ route('models.show', $m->slug) }}">Overview</a>
  <a href="{{ route('models.chat', $m->slug) }}">Model Chat</a>
  <a href="{{ route('models.log', $m->slug) }}">Raw Log</a>

  <a href="{{ route('models.prompt', [$m->slug, 1]) }}" class="{{ ($promptno == 1) ? 'active' : '' }}">Pre-Market Prompt</a>
  <a href="{{ route('models.prompt', [$m->slug, 2]) }}" class="{{ ($promptno == 2) ? 'active' : '' }}">Start Prompt</a>
  <a href="{{ route('models.prompt', [$m->slug, 3]) }}" class="{{ ($promptno == 3) ? 'active' : '' }}">Loop / Check Prompt</a>

  <a href="{{ route('models.kanban', [$m->slug, 'date' => now()->toDateString()]) }}">Kanban</a>
  {{--<a href="{{ route('models.kanban.v2', [$m->slug, 'date' => now()->toDateString()]) }}">Kanban v2</a>--}}
</div>

@if($promptno == 1)
  <div class="card" style="padding:16px">
    <div class="bold" style="margin-bottom:6px">Pre-Market Prompt</div>
    <textarea name="premarket_prompt" rows="10" class="table" disabled="disabled" style="width:100%; height: 100vh; padding:8px">{{ old('premarket_prompt',$m->premarket_prompt) }}</textarea>
    <div class="small" style="margin-top:6px">
      This is the <b>big planning prompt</b> that runs before the market opens and builds today&apos;s strategy playbook based on news, trends, funding, and your risk rules.
    </div>
  </div>
@elseif($promptno == 2)
  <div class="card" style="padding:16px">
    <div class="bold" style="margin-bottom:6px">Start Prompt</div>
    <textarea name="start_prompt" rows="16" class="table" disabled="disabled" style="width:100%; height: 100vh; padding:8px">{{ old('start_prompt',$m->start_prompt) }}</textarea>
    <div class="small" style="margin-top:6px">Used once at boot/start to initialize the model’s policy and state.</div>
  </div>
@elseif($promptno == 3)
  <div class="card" style="padding:16px">
      <div class="bold" style="margin-bottom:6px">Loop / Check Prompt</div>
      <textarea name="loop_prompt" rows="16" class="table" disabled="disabled" style="width:100%; height: 100vh; padding:8px">{{ old('loop_prompt',$m->loop_prompt) }}</textarea>
      <div class="small" style="margin-top:6px">
        Executed every <b>{{ old('check_interval_min',$m->check_interval_min ?? 15) }}</b> min.
        Should output an action + short reasoning. (e.g. HOLD / CLOSE / OPEN)
      </div>
    </div>
@endif

@endsection