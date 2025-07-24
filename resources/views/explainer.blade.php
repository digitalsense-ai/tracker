@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-8">
    <h1 class="text-3xl font-bold mb-6">📘 Forkortelser & ORB Begreber</h1>

    <div class="space-y-4">
        @foreach ($terms as $term)
            <div class="bg-white p-4 rounded shadow">
                <h2 class="text-xl font-semibold">🔹 {{ $term['label'] }}</h2>
                <p class="text-gray-700 mt-1">{{ $term['description'] }}</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
