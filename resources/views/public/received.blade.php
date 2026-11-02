@extends('layouts.public')

@section('title', __('received.title'))

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <h1>{{ __('received.title') }}</h1>

    <section class="keep" aria-labelledby="keep-heading">
        <h2 id="keep-heading">{{ __('received.keep') }}</h2>
        <dl>
            <dt>{{ __('received.reference') }}</dt>
            <dd class="code" data-reference>{{ $reference }}</dd>
            <dt>{{ __('received.pin') }}</dt>
            <dd class="code" data-pin>{{ $pin }}</dd>
        </dl>
        <p>{{ __('received.advice') }}</p>
    </section>

    @if ($emailed)
        <p>{{ __('received.emailed') }}</p>
    @endif
    <p>{{ __('received.next') }}</p>

    <form method="post" action="{{ route('grievance.done') }}" class="done">
        @csrf
        <p class="hint" id="done-hint">{{ __('received.done_hint') }}</p>
        <button type="submit" class="button" aria-describedby="done-hint">{{ __('received.done') }}</button>
    </form>
@endsection
