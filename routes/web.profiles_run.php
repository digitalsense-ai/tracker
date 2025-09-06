<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfilesRunController;
Route::get('/profiles/run', [ProfilesRunController::class, 'run'])->name('profiles.run');
