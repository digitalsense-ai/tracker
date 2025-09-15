<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\StrategyProfile;
use App\Models\ProfileResult;

class ProfilesController extends Controller {
    public function index(){ $results=ProfileResult::with('profile')->orderByDesc('score')->paginate(50); return view('profiles.index',compact('results'));}
    public function show($id){ $profile=StrategyProfile::findOrFail($id);$history=$profile->results()->orderByDesc('created_at')->limit(50)->get();return view('profiles.show',compact('profile','history'));}
}