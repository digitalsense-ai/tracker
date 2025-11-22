<?php

namespace App\Http\Controllers;

class ProfilesController extends Controller
{
    public function leaderboard()
    {
        return view('profiles.leaderboard');
    }

    public function show(string $slug)
    {
        return view('profiles.show', compact('slug'));
    }
}
