<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemSettings
{
    private const CACHE_KEY = 'ironpulse.system_settings';

    private const THEME_MODES = ['light', 'dark'];

    private const DEFAULTS = [
        'system_name' => 'IronPulse',
        'logo_path' => null,

        'theme_mode' => 'light',
        'light_theme_style' => 'white',
        'dark_theme_style' => 'graphite',
        'accent_color' => 'orange',

        'currency' => 'MXN',
        'timezone' => 'America/Mexico_City',
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',

        'business_name' => '',
        'business_rfc' => '',
        'business_address' => '',
        'business_phone' => '',
        'business_email' => '',
        'business_website' => '',
        'business_schedule' => '',

        'session_timeout' => 0,
        'password_min_length' => 8,
        'password_complexity' => false,
        'max_login_attempts' => 5,
        'lockout_minutes' => 5,
        'registration_enabled' => true,
    ];

    private const GROUPS = [
        'general' => ['system_name', 'logo_path'],
        'appearance' => ['theme_mode', 'light_theme_style', 'dark_theme_style', 'accent_color'],
        'regional' => ['currency', 'timezone', 'date_format', 'time_format'],
        'business' => [
            'business_name',
            'business_rfc',
            'business_address',
            'business_phone',
            'business_email',
            'business_website',
            'business_schedule',
        ],
        'security' => [
            'session_timeout',
            'password_min_length',
            'password_complexity',
            'max_login_attempts',
            'lockout_minutes',
            'registration_enabled',
        ],
    ];

    private const BOOLEANS = ['password_complexity', 'registration_enabled'];

    private const INTEGERS = [
        'session_timeout',
        'password_min_length',
        'max_login_attempts',
        'lockout_minutes',
    ];

    private const TEXTS = [
        'business_name',
        'business_rfc',
        'business_address',
        'business_phone',
        'business_email',
        'business_website',
        'business_schedule',
    ];

    public function all(): array
    {
        $settings = Cache::rememberForever(self::CACHE_KEY, function () {
            $saved = SystemSetting::query()
                ->pluck('value', 'key')
                ->all();

            return array_merge(self::DEFAULTS, $saved);
        });

        return $this->normalizar($settings);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $settings = $this->all();

        return $settings[$key] ?? $default;
    }

    public function update(array $settings): void
    {
        DB::transaction(function () use ($settings) {
            foreach ($settings as $key => $value) {
                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'group' => $this->groupOf($key),
                    ]
                );
            }
        });

        Cache::forget(self::CACHE_KEY);
    }

    private function groupOf(string $key): string
    {
        foreach (self::GROUPS as $group => $keys) {
            if (in_array($key, $keys, true)) {
                return $group;
            }
        }

        return 'general';
    }

    private function normalizar(array $settings): array
    {
        if (! in_array($settings['theme_mode'] ?? null, self::THEME_MODES, true)) {
            $settings['theme_mode'] = self::DEFAULTS['theme_mode'];
        }

        foreach (self::BOOLEANS as $key) {
            $settings[$key] = filter_var(
                $settings[$key] ?? self::DEFAULTS[$key],
                FILTER_VALIDATE_BOOLEAN
            );
        }

        foreach (self::INTEGERS as $key) {
            $settings[$key] = (int) ($settings[$key] ?? self::DEFAULTS[$key]);
        }

        foreach (self::TEXTS as $key) {
            $settings[$key] = (string) ($settings[$key] ?? '');
        }

        return $settings;
    }
}