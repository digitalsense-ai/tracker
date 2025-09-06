<?php
use Illuminate\Support\Facades\Route;
Route::get('/signals-pretty', fn() => view('signals.pretty'))->name('signals.pretty');
