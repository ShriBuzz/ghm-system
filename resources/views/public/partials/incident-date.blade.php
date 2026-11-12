{{-- The incident date as three selects (P5): Bikram Sambat on the Nepali form, AD on the English one (U9).
     The BS fields have their own names, so a language switch never carries a BS month into the AD selects;
     StoreGrievanceRequest::prepareForValidation() turns either into an AD date. --}}
@php
    $error = $errors->first('incident_date');
    // Each box points at the date's hint and error, as every other field does: a jump from the error summary lands on the day box.
    $describedBy = 'incident_date-hint'.($error ? ' incident_date-error' : '');
    if (app()->getLocale() === 'ne') {
        $prefix = 'incident_bs_';
        $thisYear = \App\Support\BsDate::fromAd(now())[0];
        $years = range($thisYear, max($thisYear - 10, \App\Support\BsDate::FIRST_YEAR));
        $months = collect(range(1, 12))->mapWithKeys(fn (int $m) => [$m => __("bs_month.{$m}")]);
        $days = range(1, 32);
    } else {
        $prefix = 'incident_';
        $thisYear = (int) now()->year;
        $years = range($thisYear, $thisYear - 10);
        $months = collect(range(1, 12))->mapWithKeys(fn (int $m) => [$m => \Illuminate\Support\Carbon::create(2000, $m, 1)->locale(app()->getLocale())->translatedFormat('F')]);
        $days = range(1, 31);
    }
@endphp
<fieldset @class(['date-parts-group', 'field-error' => $error]) aria-describedby="incident_date-hint{{ $error ? ' incident_date-error' : '' }}">
    <legend>{{ __('form.when.legend') }}</legend>
    <p class="hint" id="incident_date-hint">{{ __('form.when.hint') }}</p>
    @if ($error)
        <p class="error" id="incident_date-error"><span class="visually-hidden">{{ __('form.errors.prefix') }}</span> {{ $error }}</p>
    @endif
    <div class="date-parts">
        <div class="field">
            <label for="{{ $prefix }}day">{{ __('form.when.day') }}</label>
            <select id="{{ $prefix }}day" name="{{ $prefix }}day" aria-describedby="{{ $describedBy }}" @if ($error) aria-invalid="true" @endif>
                <option value="">{{ __('form.choose') }}</option>
                <option value="unknown" @selected(old($prefix.'day') === 'unknown')>{{ __('form.when.unknown_day') }}</option>
                @foreach ($days as $day)
                    <option value="{{ $day }}" @selected(old($prefix.'day') === (string) $day)>{{ $day }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="{{ $prefix }}month">{{ __('form.when.month') }}</label>
            <select id="{{ $prefix }}month" name="{{ $prefix }}month" aria-describedby="{{ $describedBy }}" @if ($error) aria-invalid="true" @endif>
                <option value="">{{ __('form.choose') }}</option>
                @foreach ($months as $number => $month)
                    <option value="{{ $number }}" @selected(old($prefix.'month') === (string) $number)>{{ $month }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="{{ $prefix }}year">{{ __('form.when.year') }}</label>
            <select id="{{ $prefix }}year" name="{{ $prefix }}year" aria-describedby="{{ $describedBy }}" @if ($error) aria-invalid="true" @endif>
                @foreach ($years as $year)
                    <option value="{{ $year }}" @selected((string) old($prefix.'year', $thisYear) === (string) $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>
</fieldset>
