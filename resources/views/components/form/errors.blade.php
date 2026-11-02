@props(['anchors' => []])
@if ($errors->any())
    <div class="error-summary" role="alert" tabindex="-1" data-error-summary>
        <h2>{{ __('form.errors.heading') }}</h2>
        <ul>
            @foreach ($errors->getMessages() as $field => $messages)
                <li><a href="#{{ $anchors[$field] ?? $field }}">{{ $messages[0] }}</a></li>
            @endforeach
        </ul>
    </div>
@endif
