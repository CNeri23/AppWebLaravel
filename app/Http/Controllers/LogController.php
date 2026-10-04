<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Services\SystemSettings;
use Illuminate\View\View;

class LogController extends Controller
{
    public function index(SystemSettings $settings): View
    {
        $logs = Log::with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('logs.index', [
            'logs' => $logs,
            'dateFormat' => $settings->get('date_format', 'd/m/Y'),
            'timeFormat' => $settings->get('time_format', 'H:i'),
            'timezone' => $settings->get('timezone', config('app.timezone')),
        ]);
    }
}