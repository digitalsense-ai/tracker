@extends('layouts.app')
@section('title','Settings')
@section('content')
<h1 class="mb-3">Strategy Settings</h1>
<form method="post" action="/settings">
  @csrf
  <div class="row g-3">
    <div class="col-md-3">
      <label class="form-label">Currency</label>
      <select name="currency" class="form-select">
        @foreach(['kr','USD','$','EUR','€'] as $c)
          <option value="{{ $c }}" @selected(($settings['currency'] ?? 'kr')===$c)>{{ $c }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Position size</label>
      <input type="number" step="1" name="position" class="form-control" value="{{ $settings['position'] ?? 1000 }}">
      <small class="text-muted">Per trade (in selected currency)</small>
    </div>
    <div class="col-md-3">
      <label class="form-label">Fee % (fraction)</label>
      <input type="number" step="0.0001" name="fee_percent" class="form-control" value="{{ $settings['fee_percent'] ?? 0.001 }}">
      <small class="text-muted">0.001 = 0.1%</small>
    </div>
    <div class="col-md-3">
      <label class="form-label">Min fee</label>
      <input type="number" step="0.01" name="fee_min" class="form-control" value="{{ $settings['fee_min'] ?? 2.0 }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Opening range (min)</label>
      <input type="number" name="range_min" class="form-control" value="{{ $settings['range_min'] ?? 15 }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Require retest</label>
      <select name="require_retest" class="form-select">
        <option value="1" @selected(($settings['require_retest'] ?? 1)==1)>Yes</option>
        <option value="0" @selected(($settings['require_retest'] ?? 1)==0)>No</option>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Entry buffer %</label>
      <input type="number" step="0.01" name="entry_buffer" class="form-control" value="{{ $settings['entry_buffer'] ?? 0.05 }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">SL buffer %</label>
      <input type="number" step="0.01" name="sl_buffer" class="form-control" value="{{ $settings['sl_buffer'] ?? 0.05 }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Session start</label>
      <input type="time" name="session_start" class="form-control" value="{{ $settings['session_start'] ?? '09:30' }}">
    </div>
    <div class="col-md-3">
      <label class="form-label">Session end</label>
      <input type="time" name="session_end" class="form-control" value="{{ $settings['session_end'] ?? '16:00' }}">
    </div>
  </div>
  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary">Save</button>
    <a href="/status" class="btn btn-outline-secondary">Back to Status</a>
  </div>
</form>
@endsection
