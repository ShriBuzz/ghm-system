{{-- The incident date in AD, as three selects (P5). A Bikram Sambat version, if NBI asks for one
     (clarification question 8), replaces this partial and StoreGrievanceRequest::prepareForValidation(). --}}
@php
    $error = $errors->first('incident_date');
    $thisYear = (int) now()->year;
    $months = collect(range(1, 12))->mapWithKeys(fn (int $m) => [$m => \Illuminate\Support\Carbon::create(2000, $m, 1)->locale(app()->getLocale())->translatedFormat('F')]);
@endphp
<fieldset @class(['date-parts-group', 'field-error' => $error]) aria-describedby="incident_date-hint{{ $error ? ' incident_date-error' : '' }}">
    <legend>{{ __('form.when.legend') }}</legend>
    <p class="hint" id="incident_date-hint">{{ __('form.when.hint') }}</p>
    @if ($error)
        <p class="error" id="incident_date-error"><span class="visually-hidden">{{ __('form.errors.prefix') }}</span> {{ $error }}</p>
    @endif
    <div class="date-parts">
        <div class="field">
            <label for="incident_day">{{ __('form.when.day') }}</label>
            <select id="incident_day" name="incident_day" @if ($error) aria-invalid="true" @endif>
                <option value="">{{ __('form.choose') }}</option>
                <option value="unknown" @selected(old('incident_day') === 'unknown')>{{ __('form.when.unknown_day') }}</option>
                @foreach (range(1, 31) as $day)
                    <option value="{{ $day }}" @selected(old('incident_day') === (string) $day)>{{ $day }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="incident_month">{{ __('form.when.month') }}</label>
            <select id="incident_month" name="incident_month" @if ($error) aria-invalid="true" @endif>
                <option value="">{{ __('form.choose') }}</option>
                @foreach ($months as $number => $month)
                    <option value="{{ $number }}" @selected(old('incident_month') === (string) $number)>{{ $month }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="incident_year">{{ __('form.when.year') }}</label>
            <select id="incident_year" name="incident_year" @if ($error) aria-invalid="true" @endif>
                @foreach (range($thisYear, $thisYear - 10) as $year)
                    <option value="{{ $year }}" @selected((string) old('incident_year', $thisYear) === (string) $year)>{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>
</fieldset>
