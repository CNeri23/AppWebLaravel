@extends('layouts.app')

@section('title', 'Preferencias')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/preferences.css') }}">
@endpush

@section('content')

<div class="container-fluid py-4 preferencias-page">
    <div class="theme-style-grid">
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

        @foreach ($lightThemeStyles as $value => $style)
            <label class="theme-style-option">
                <input type="radio"
                    name="light_theme_style"
                    value="{{ $value }}"
                    {{ $preferencias['light_theme_style'] === $value ? 'checked' : '' }}>

                <span class="theme-style-content">
                    <span class="theme-preview {{ $style['class'] }}">
                        <span class="theme-preview-sidebar"></span>

                        <span class="theme-preview-main">
                            <span></span>
                            <span></span>
                            <span></span>
                        </span>
                    </span>

                    <span class="theme-style-info">
                        <strong>{{ $style['name'] }}</strong>
                        <small>{{ $style['description'] }}</small>
                    </span>
                </span>
            </label>
        @endforeach

        @foreach ($darkThemeStyles as $value => $style)
            <label class="theme-style-option">
                <input type="radio"
                    name="dark_theme_style"
                    value="{{ $value }}"
                    {{ $preferencias['dark_theme_style'] === $value ? 'checked' : '' }}>

                <span class="theme-style-content">
                    <span class="theme-preview {{ $style['class'] }}">
                        <span class="theme-preview-sidebar"></span>

                        <span class="theme-preview-main">
                            <span></span>
                            <span></span>
                            <span></span>
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
</div>

@endsection

@push('scripts')
    <script src="{{ asset('js/preferences.js') }}"></script>
@endpush
