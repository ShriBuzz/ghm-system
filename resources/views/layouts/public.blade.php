@php($other = app()->getLocale() === 'ne' ? 'en' : 'ne')
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@hasSection('title')@yield('title') · @endif{{ __('site.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/public.css') }}">
</head>
<body>
    <header class="site-header">
        <a class="site-name" href="{{ route('home') }}">{{ __('site.name') }}</a>
        <form class="language-switch" method="post" action="{{ route('language') }}">
            <input type="hidden" name="locale" value="{{ $other }}">
            <button type="submit" lang="{{ $other }}">{{ $other === 'en' ? 'English' : 'नेपाली' }}</button>
        </form>
    </header>
    <main>
        @yield('content')
    </main>
</body>
</html>
