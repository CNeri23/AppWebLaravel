<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserPreference;

class UserPreferences
{
    private const DEFAULTS = [
        'theme_mode' => 'light',
        'light_theme_style' => 'white',
        'dark_theme_style' => 'graphite',
    ];

    private const THEME_MODES = [
        'light',
        'dark',
    ];

    private const LIGHT_STYLES = [
        'white',
        'mist',
        'sky',
    ];

    private const DARK_STYLES = [
        'graphite',
        'charcoal',
        'black',
    ];

    public function get(User $user): array
    {
        $preference = $user->preference()->firstOrCreate(
            ['user_id' => $user->id],
            self::DEFAULTS
        );

        $themeMode = in_array(
            $preference->theme_mode,
            self::THEME_MODES,
            true
        )
            ? $preference->theme_mode
            : self::DEFAULTS['theme_mode'];

        $lightThemeStyle = in_array(
            $preference->light_theme_style,
            self::LIGHT_STYLES,
            true
        )
            ? $preference->light_theme_style
            : self::DEFAULTS['light_theme_style'];

        $darkThemeStyle = in_array(
            $preference->dark_theme_style,
            self::DARK_STYLES,
            true
        )
            ? $preference->dark_theme_style
            : self::DEFAULTS['dark_theme_style'];

        return [
            'theme_mode' => $themeMode,
            'light_theme_style' => $lightThemeStyle,
            'dark_theme_style' => $darkThemeStyle,
        ];
    }

    public function update(User $user, array $preferences): array
    {
        $actuales = $this->get($user);

        $themeMode = $preferences['theme_mode']
            ?? $actuales['theme_mode'];

        if (! in_array($themeMode, self::THEME_MODES, true)) {
            $themeMode = self::DEFAULTS['theme_mode'];
        }

        $lightThemeStyle = $preferences['light_theme_style']
            ?? $actuales['light_theme_style'];

        if (! in_array($lightThemeStyle, self::LIGHT_STYLES, true)) {
            $lightThemeStyle = self::DEFAULTS['light_theme_style'];
        }

        $darkThemeStyle = $preferences['dark_theme_style']
            ?? $actuales['dark_theme_style'];

        if (! in_array($darkThemeStyle, self::DARK_STYLES, true)) {
            $darkThemeStyle = self::DEFAULTS['dark_theme_style'];
        }

        $preference = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            [
                'theme_mode' => $themeMode,
                'light_theme_style' => $lightThemeStyle,
                'dark_theme_style' => $darkThemeStyle,
            ]
        );

        return [
            'theme_mode' => $preference->theme_mode,
            'light_theme_style' => $preference->light_theme_style,
            'dark_theme_style' => $preference->dark_theme_style,
        ];
    }
}
