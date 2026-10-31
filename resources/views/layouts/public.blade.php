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
    <title>@hasSection('title')@yield('title') · @endif{{ __('site.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
    @foreach ($languages->except(app()->getLocale()) as $code => $language)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $language['url'] }}">
    @endforeach
</head>
<body>
    <header class="site-header">
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
