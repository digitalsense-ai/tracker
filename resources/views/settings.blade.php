@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Settings</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('settings.store') }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Currency</label>
            <input type="text" name="CURRENCY" class="form-control" value="{{ $settings['CURRENCY'] ?? 'kr' }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Position Size</label>
            <input type="number" name="POSITION_SIZE" class="form-control" value="{{ $settings['POSITION_SIZE'] ?? 1000 }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Fee %</label>
            <input type="text" name="FEE_PERCENT" class="form-control" value="{{ $settings['FEE_PERCENT'] ?? 0.001 }}">
        </div>

        <div class="mb-3">
            <label class="form-label">Range Minutes</label>
            <input type="number" name="STRATEGY_RANGE_MINUTES" class="form-control" value="{{ $settings['STRATEGY_RANGE_MINUTES'] ?? 15 }}">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="STRATEGY_REQUIRE_RETEST" class="form-check-input" {{ !empty($settings['STRATEGY_REQUIRE_RETEST']) ? 'checked' : '' }}>
            <label class="form-check-label">Require Retest</label>
        </div>

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
