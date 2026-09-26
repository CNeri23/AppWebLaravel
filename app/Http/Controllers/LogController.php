<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        $logs = Log::with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('logs.index', compact('logs'));
    }
}