@extends('layouts.public')

@section('title', __('form.title'))

@push('head')
    <script src="{{ asset('js/form.js') }}" defer></script>
@endpush

@section('content')
    <h1>{{ __('form.title') }}</h1>

    <section class="notice" aria-labelledby="notice-heading">
        <h2 id="notice-heading">{{ __('form.notice_heading') }}</h2>
        @foreach ($notice as $paragraph)
            <p>{!! nl2br(e($paragraph)) !!}</p>
        @endforeach
    </section>

    <form method="post" action="{{ route('grievance.store') }}" enctype="multipart/form-data" novalidate data-grievance>
        @csrf

        <x-form.errors :anchors="[
            'is_anonymous' => 'is_anonymous-1',
            'preferred_contact' => 'preferred_contact-phone',
            'incident_date' => 'incident_day',
            'recurrence' => 'recurrence-once',
        ]" />

        <div class="section">
            <x-form.choices name="is_anonymous" :legend="__('form.identity.legend')" checked="1"
                :options="['1' => __('form.identity.anonymous'), '0' => __('form.identity.named')]"
                :hints="['1' => __('form.identity.anonymous_hint'), '0' => __('form.identity.named_hint')]" />

            <fieldset class="contact">
                <legend>{{ __('form.contact.legend') }}</legend>
                <p class="hint">{{ __('form.contact.hint') }}</p>
                <x-form.field name="name" :label="__('form.name')" autocomplete="name" maxlength="255" />
                <x-form.field name="phone" type="tel" :label="__('form.phone')" autocomplete="tel" inputmode="tel" maxlength="30" />
                <x-form.field name="email" type="email" :label="__('form.email')" :hint="__('form.email_hint')" autocomplete="email" maxlength="255" />
                <x-form.choices name="preferred_contact" :legend="__('form.preferred')"
                    :options="collect(\App\Enums\ContactMethod::cases())->mapWithKeys(fn ($method) => [$method->value => $method->getLabel()])->all()" />
            </fieldset>
        </div>

        <div class="section">
            <h2>{{ __('form.what.heading') }}</h2>
            <x-form.field name="category_id" type="select" :label="__('form.category')" :options="$categories" required />
            <x-form.field name="location_id" type="select" :label="__('form.location')" :options="$locations" required />
            @include('public.partials.incident-date')
            <x-form.choices name="recurrence" :legend="__('form.recurrence')"
                :options="collect(\App\Enums\Recurrence::cases())->mapWithKeys(fn ($recurrence) => [$recurrence->value => $recurrence->getLabel()])->all()" />
            <x-form.field name="description" type="textarea" :label="__('form.description')" :hint="__('form.description_hint')" maxlength="5000" required />
            <x-form.field name="attachment" type="file" :label="__('form.attachment')" :hint="__('form.attachment_hint')"
                accept="image/jpeg,image/png,image/webp,application/pdf" data-max-bytes="{{ config('ghm.upload_max_kb') * 1024 }}" data-too-large="{{ __('form.attachment_too_large') }}" />
        </div>

        <div class="section">
            <h2>{{ __('form.about.heading') }}</h2>
            <p class="hint">{{ __('form.about.hint') }}</p>
            @foreach (\App\Models\Grievance::OPTIONAL_ANSWERS as $field => $enum)
                <x-form.field :name="$field" type="select" :label="__('form.about.'.$field)"
                    :options="collect($enum::cases())->mapWithKeys(fn ($answer) => [$answer->value => $answer->getLabel()])->all()" />
            @endforeach
        </div>

        <button type="submit" class="button button-primary">{{ __('form.submit') }}</button>
    </form>
@endsection
