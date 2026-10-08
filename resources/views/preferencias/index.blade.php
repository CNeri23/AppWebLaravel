@extends('layouts.app')
@section('content')

<div class="container-fluid py-4 preferencias-page">
    <div class="preferences-card">
        <div class="preferences-header">
            <div>
                <h1>Preferencias</h1>
                <p>Personaliza la apariencia de IronPulse para tu cuenta.</p>
            </div>
        </div>


    @php
        $lightThemeStyles = [
            'white' => [
                'name' => 'White',
                'description' => 'Claro limpio',
                'class' => 'theme-preview-white',
            ],
            'mist' => [
                'name' => 'Mist',
                'description' => 'Claro suave',
                'class' => 'theme-preview-mist',
            ],
            'sky' => [
                'name' => 'Sky',
                'description' => 'Claro fresco',
                'class' => 'theme-preview-sky',
            ],
        ];

        $darkThemeStyles = [
            'graphite' => [
                'name' => 'Graphite',
                'description' => 'Oscuro elegante',
                'class' => 'theme-preview-graphite',
            ],
            'charcoal' => [
                'name' => 'Charcoal',
                'description' => 'Oscuro neutro',
                'class' => 'theme-preview-charcoal',
            ],
            'black' => [
                'name' => 'Black',
                'description' => 'Negro profundo',
                'class' => 'theme-preview-black',
            ],
        ];
    @endphp

    <section class="theme-section">
        <div class="theme-section-header">
            <div>
                <h2>Tema claro</h2>
                <p>Selecciona el estilo que quieres utilizar cuando estés en modo claro.</p>
            </div>
        </div>

        <div class="theme-style-grid">
            @foreach ($lightThemeStyles as $value => $style)
                <label class="theme-style-option">
                    <input
                        type="radio"
                        name="light_theme_style"
                        value="{{ $value }}"
                        {{ $preferencias['light_theme_style'] === $value ? 'checked' : '' }}
                    >

                    <span class="theme-style-content">
                        <span class="theme-preview {{ $style['class'] }}">
                            <span class="theme-preview-topbar"></span>

                            <span class="theme-preview-sidebar">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>

                            <span class="theme-preview-main">
                                <span class="theme-preview-heading"></span>
                                <span class="theme-preview-card"></span>
                                <span class="theme-preview-card"></span>
                            </span>

                            <span class="theme-preview-check">
                                <i class="fa-solid fa-check"></i>
                            </span>
                        </span>

                        <span class="theme-style-info">
                            <strong>{{ $style['name'] }}</strong>
                            <small>{{ $style['description'] }}</small>
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
    </section>

    <div class="theme-section-divider"></div>

    <section class="theme-section">
        <div class="theme-section-header">
            <div>
                <h2>Tema oscuro</h2>
                <p>Selecciona el estilo que quieres utilizar cuando estés en modo oscuro.</p>
            </div>
        </div>

        <div class="theme-style-grid">
            @foreach ($darkThemeStyles as $value => $style)
                <label class="theme-style-option">
                    <input
                        type="radio"
                        name="dark_theme_style"
                        value="{{ $value }}"
                        {{ $preferencias['dark_theme_style'] === $value ? 'checked' : '' }}
                    >

                    <span class="theme-style-content">
                        <span class="theme-preview {{ $style['class'] }}">
                            <span class="theme-preview-topbar"></span>

                            <span class="theme-preview-sidebar">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>

                            <span class="theme-preview-main">
                                <span class="theme-preview-heading"></span>
                                <span class="theme-preview-card"></span>
                                <span class="theme-preview-card"></span>
                            </span>

                            <span class="theme-preview-check">
                                <i class="fa-solid fa-check"></i>
                            </span>
                        </span>

                        <span class="theme-style-info">
                            <strong>{{ $style['name'] }}</strong>
                            <small>{{ $style['description'] }}</small>
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
    </section>
</div>


</div>

@endsection
