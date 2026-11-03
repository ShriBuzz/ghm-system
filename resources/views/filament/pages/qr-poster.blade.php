<x-filament-panels::page>
    {{-- The poster (P10): both languages, the code large, the address in words for those who type it. --}}
    <div class="ghm-poster">
        <p class="ghm-poster-title" lang="ne">{{ __('form.title', locale: 'ne') }}</p>
        <p class="ghm-poster-title" lang="en">{{ __('form.title', locale: 'en') }}</p>
        <img class="ghm-poster-code" src="{{ $this->svgDataUri() }}" alt="QR code for {{ $this->formUrl() }}">
        <p lang="ne">{{ __('poster.scan', locale: 'ne') }}</p>
        <p lang="en">{{ __('poster.scan', locale: 'en') }}</p>
        <p class="ghm-poster-url">{{ $this->formUrl() }}</p>
    </div>
</x-filament-panels::page>
