@php $S = app(\App\Services\SettingsService::class); $theme = $S->get('ui.theme','light'); @endphp
@if($theme==='dark')
<link rel="stylesheet" href="{{ asset('css/tracker-theme-dark.css') }}?v=2025-09-02-2">
@else
<link rel="stylesheet" href="{{ asset('css/tracker-theme.css') }}?v=2025-09-02-2">
@endif