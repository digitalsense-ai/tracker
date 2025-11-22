@extends('layouts.app')
@section('content')
<h1>Signals</h1>

@php
  $payload = $payload ?? $signals ?? null;
@endphp

<div class="card">
  @if(is_string($payload))
    <pre style="white-space:pre-wrap;">{{ $payload }}</pre>
  @elseif($payload)
    <pre style="white-space:pre-wrap;">{{ json_encode($payload, JSON_PRETTY_PRINT) }}</pre>
  @else
    <p class="text-muted">No signals available.</p>
  @endif
</div>
@endsection
