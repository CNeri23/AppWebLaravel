<?php

namespace App\Http\Controllers;

use App\Services\UserPreferences;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PreferencesController extends Controller
{
    public function index(
        Request $request,
        UserPreferences $preferences
    ): View {
        return view('preferencias.index', [
            'preferencias' => $preferences->get($request->user()),
        ]);
    }
}
