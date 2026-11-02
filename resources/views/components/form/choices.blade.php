@props(['name', 'legend', 'options', 'hints' => [], 'hint' => null, 'checked' => null])
@php
    $error = $errors->first($name);
    $current = (string) old($name, $checked);
@endphp
<fieldset @class(['choices', 'field-error' => $error]) @if ($hint || $error) aria-describedby="{{ trim(($hint ? "{$name}-hint " : '').($error ? "{$name}-error" : '')) }}" @endif>
    <legend>{{ $legend }}</legend>
    @if ($hint)
        <p class="hint" id="{{ $name }}-hint">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="error" id="{{ $name }}-error"><span class="visually-hidden">{{ __('form.errors.prefix') }}</span> {{ $error }}</p>
    @endif
    @foreach ($options as $value => $label)
        <label class="choice" for="{{ $name }}-{{ $value }}">
            <input type="radio" id="{{ $name }}-{{ $value }}" name="{{ $name }}" value="{{ $value }}" @checked($current === (string) $value)
                @if (isset($hints[$value])) aria-describedby="{{ $name }}-{{ $value }}-hint" @endif>
            <span>
                {{ $label }}
                @if (isset($hints[$value]))
                    <span class="hint" id="{{ $name }}-{{ $value }}-hint">{{ $hints[$value] }}</span>
                @endif
            </span>
        </label>
    @endforeach
</fieldset>
