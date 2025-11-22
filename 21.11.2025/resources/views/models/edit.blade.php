@extends('layouts.app')
@section('title', $model->exists ? 'Edit Model' : 'New Model')
@section('header_title', $model->exists ? 'Edit Model' : 'New Model')

@section('content')
  @if (session('ok'))
    <div class="card" style="padding:12px;margin-bottom:12px;color:#065f46;background:#ecfdf5;border:1px solid #a7f3d0;">
      {{ session('ok') }}
    </div>
  @endif

  <form method="post" action="{{ $model->exists ? route('models.update',$model) : route('models.store') }}">
    @csrf
    @if($model->exists) @method('PUT') @endif

    <section class="card" style="padding:16px">
      <div class="grid" style="grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <label class="bold">Name</label>
          <input name="name" value="{{ old('name',$model->name) }}" class="table" style="width:100%;padding:8px" required>
        </div>
        <div>
          <label class="bold">Wallet</label>
          <input name="wallet" value="{{ old('wallet',$model->wallet) }}" class="table" style="width:100%;padding:8px">
        </div>

        <div>
          <label class="bold">Equity ($)</label>
          <input name="equity" type="number" step="0.01" value="{{ old('equity',$model->equity) }}" class="table" style="width:100%;padding:8px">
        </div>
        <div>
          <label class="bold">Return %</label>
          <input name="return_pct" type="number" step="0.01" value="{{ old('return_pct',$model->return_pct) }}" class="table" style="width:100%;padding:8px">
        </div>

        <div>
          <label class="bold">Check Interval (minutes)</label>
          <input name="check_interval_min" type="number" min="1" value="{{ old('check_interval_min',$model->check_interval_min ?? 15) }}" class="table" style="width:100%;padding:8px" required>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
          <label class="bold">Active</label>
          <input type="checkbox" name="active" value="1" {{ old('active',$model->active) ? 'checked' : '' }}>
        </div>
      </div>
    </section>

    <section class="grid" style="grid-template-columns:1fr 1fr;gap:16px;margin-top:16px">
      <div class="card" style="padding:16px">
        <div class="bold" style="margin-bottom:6px">Start Prompt</div>
        <textarea name="start_prompt" rows="16" class="table" style="width:100%;padding:8px">{{ old('start_prompt',$model->start_prompt) }}</textarea>
        <div class="small" style="margin-top:6px">Used once at boot/start to initialize the model’s policy and state.</div>
      </div>

      <div class="card" style="padding:16px">
        <div class="bold" style="margin-bottom:6px">Loop / Check Prompt</div>
        <textarea name="loop_prompt" rows="16" class="table" style="width:100%;padding:8px">{{ old('loop_prompt',$model->loop_prompt) }}</textarea>
        <div class="small" style="margin-top:6px">
          Executed every <b>{{ old('check_interval_min',$model->check_interval_min ?? 15) }}</b> min.
          Should output an action + short reasoning. (e.g. HOLD / CLOSE / OPEN)
        </div>
      </div>
    </section>

    <div style="margin-top:16px;display:flex;gap:12px">
      <button class="tab active" type="submit">Save</button>
      <a class="tab" href="{{ route('models.index') }}">Back</a>
    </div>
  </form>
@endsection
