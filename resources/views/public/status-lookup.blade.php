@extends('layouts.public')

@section('title', __('track.lookup_title'))

@push('head')
    <meta name="robots" content="noindex">
    <script src="{{ asset('js/form.js') }}" defer></script>
@endpush

@section('content')
    <h1>{{ __('track.lookup_title') }}</h1>

    @if (session('status_notice'))
        <p class="notice-line" role="status" tabindex="-1" data-notice>{{ session('status_notice') }}</p>
    @endif

    <p>{{ __('track.intro') }}</p>

    <form method="post" action="{{ route('status.check') }}" class="stack" novalidate>
        @csrf
        <x-form.errors :anchors="['lookup' => 'reference']" :heading="__('track.errors.heading')" />
        <x-form.field name="reference" :label="__('track.reference')" :hint="__('track.reference_hint')" required
            autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="20" />
        <x-form.field name="pin" :label="__('track.pin')" :hint="__('track.pin_hint')" required
            inputmode="numeric" autocomplete="off" maxlength="20" />
        <button type="submit" class="button button-primary">{{ __('track.check') }}</button>
    </form>

    <p class="aside-link"><a href="{{ route('home') }}">{{ __('track.new') }}</a></p>
@endsection
