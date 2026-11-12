@php
    // The same page in each language; pages without a named route fall back to home.
    $route = request()->route();
    $languages = collect(['ne' => 'नेपाली', 'en' => 'English'])->map(fn (string $name, string $code) => [
        'name' => $name,
        'url' => $route?->getName()
            ? route($route->getName(), ['locale' => $code] + Arr::only($route->parameters(), $route->parameterNames()))
            : route('home', ['locale' => $code]),
    ]);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- A page sent back with errors says so first, for screen readers (S9). --}}
    <title>@if ($errors->any()){{ __('form.errors.prefix') }} @endif@hasSection('title')@yield('title') · @endif{{ __('site.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
    @stack('head')
    @foreach ($languages->except(app()->getLocale()) as $code => $language)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $language['url'] }}">
    @endforeach
</head>
<body>
    <header class="site-header">
        {{-- The company's emergency line (U10), the whole sentence a phone link so it is easy to tap. --}}
        <p class="emergency"><a href="tel:100">{{ __('emergency.line') }}</a></p>
        <a class="site-name" href="{{ route('home') }}">{{ __('site.name') }}</a>
        <nav class="language-switch" aria-label="{{ __('language.label') }}">
            <ul>
                @foreach ($languages as $code => $language)
                    <li>
                        @if ($code === app()->getLocale())
                            <span lang="{{ $code }}" aria-current="true">{{ $language['name'] }}</span>
                        @else
                            <a href="{{ $language['url'] }}" lang="{{ $code }}" hreflang="{{ $code }}">{{ $language['name'] }}</a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
