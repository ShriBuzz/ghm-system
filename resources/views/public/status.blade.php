@extends('layouts.public')

@section('title', __('track.title'))

@push('head')
    <meta name="robots" content="noindex">
    <script src="{{ asset('js/form.js') }}" defer></script>
@endpush

@section('content')
    <h1>{{ __('track.title') }}</h1>

    @if (session('status_notice'))
        <p class="notice-line" role="status">{{ session('status_notice') }}</p>
    @endif

    <dl class="facts">
        <div>
            <dt>{{ __('track.reference') }}</dt>
            <dd class="code-small">{{ $case->reference }}</dd>
        </div>
        <div>
            <dt>{{ __('track.status') }}</dt>
            <dd class="status-value">{{ $case->status->getLabel() }}</dd>
        </div>
        <div>
            <dt>{{ __('track.received') }}</dt>
            <dd>{{ $case->received_at->locale(app()->getLocale())->translatedFormat('j F Y') }}</dd>
        </div>
    </dl>

    <section aria-labelledby="messages-heading">
        <h2 id="messages-heading">{{ __('track.messages') }}</h2>
        @if ($messages->isEmpty())
            <p class="hint">{{ __('track.none') }}</p>
        @else
            <ol class="thread">
                @foreach ($messages as $message)
                    @php $mine = $message->direction === \App\Enums\MessageDirection::Complainant; @endphp
                    <li @class(['message', 'message-mine' => $mine])>
                        <p class="message-meta">
                            <strong>{{ $mine ? __('track.you') : __('track.team') }}</strong>
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->locale(app()->getLocale())->translatedFormat('j F Y, H:i') }}</time>
                        </p>
                        <p class="message-body">{!! nl2br(e($message->body)) !!}</p>
                    </li>
                @endforeach
            </ol>
        @endif
    </section>

    <form method="post" action="{{ route('status.reply') }}" class="stack reply" novalidate>
        @csrf
        <x-form.errors />
        <div @class(['field', 'field-error' => $errors->has('body')])>
            <label for="body">{{ __('track.add') }}</label>
            <p class="hint" id="body-hint">{{ __('track.add_hint') }}</p>
            @error('body')
                <p class="error" id="body-error"><span class="visually-hidden">{{ __('form.errors.prefix') }}</span> {{ $message }}</p>
            @enderror
            <textarea id="body" name="body" rows="5" maxlength="5000" required aria-describedby="body-hint{{ $errors->has('body') ? ' body-error' : '' }}" @error('body') aria-invalid="true" @enderror>{{ old('body', $draft) }}</textarea>
        </div>
        <button type="submit" class="button button-primary">{{ __('track.send') }}</button>
    </form>

    <form method="post" action="{{ route('status.close') }}" class="done">
        @csrf
        <p class="hint" id="close-hint">{{ __('track.close_hint') }}</p>
        <button type="submit" class="button" aria-describedby="close-hint">{{ __('track.close') }}</button>
    </form>
@endsection
