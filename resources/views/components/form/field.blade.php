@props(['name', 'label', 'type' => 'text', 'hint' => null, 'options' => [], 'placeholder' => null, 'required' => false])
@php
    $id = $name;
    $error = $errors->first($name);
    $describedBy = trim(($hint ? "{$id}-hint " : '').($error ? "{$id}-error" : ''));
    $value = old($name);
@endphp
<div @class(['field', 'field-error' => $error])>
    <label for="{{ $id }}">{{ $label }}</label>
    @if ($hint)
        <p class="hint" id="{{ $id }}-hint">{{ $hint }}</p>
    @endif
    @if ($error)
        <p class="error" id="{{ $id }}-error"><span class="visually-hidden">{{ __('form.errors.prefix') }}</span> {{ $error }}</p>
    @endif
    @if ($type === 'select')
        <select id="{{ $id }}" name="{{ $name }}" @required($required) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif>
            <option value="">{{ $placeholder ?? __('form.choose') }}</option>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="8" @required($required) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif {{ $attributes }}>{{ $value }}</textarea>
    @elseif ($type === 'file')
        <input id="{{ $id }}" name="{{ $name }}" type="file" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif {{ $attributes }}>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}" @required($required) @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif {{ $attributes }}>
    @endif
</div>
