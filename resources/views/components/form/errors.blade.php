@props(['anchors' => [], 'heading' => null])
@if ($errors->any())
    @php
        // A refusal (spam check, hourly limit, an ended session) is about the sending, not an answer:
        // it gets its own heading and no link to a field.
        $refusal = $errors->first('form');
        $fields = \Illuminate\Support\Arr::except($errors->getMessages(), 'form');
    @endphp
    <div class="error-summary" role="alert" tabindex="-1" data-error-summary>
        <h2>{{ $refusal ? __('form.errors.refused') : ($heading ?? __('form.errors.heading')) }}</h2>
        @if ($refusal)
            <p>{{ $refusal }}</p>
        @endif
        @if ($fields)
            <ul>
                @foreach ($fields as $field => $messages)
                    <li><a href="#{{ $anchors[$field] ?? $field }}">{{ $messages[0] }}</a></li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
