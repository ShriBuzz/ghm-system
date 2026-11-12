<?php

namespace App\Http\Requests;

use App\Enums\ContactMethod;
use App\Enums\Recurrence;
use App\Models\Grievance;
use App\Support\AttachmentStore;
use App\Support\BsDate;
use App\Support\VisitorKey;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;

/**
 * A new grievance from the public form (WP1). Offline entry (A6) uses the
 * same caseRules(), so a field change is made once for both.
 */
class StoreGrievanceRequest extends FormRequest
{
    /**
     * Back to the form by name: public pages send no referrer, so Laravel's "back" has nothing to go on.
     */
    protected $redirectRoute = 'home';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::caseRules() + [
            'attachment' => ['nullable', AttachmentStore::rule()],
        ];
    }

    /**
     * Spam protection (P9): a trap field people never see, a minimum time to fill the form, and a
     * limit per visitor per hour. A refusal keeps what was typed and says so in plain words.
     *
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (filled($this->input('website')) || $this->sentTooSoon()) {
                $validator->errors()->add('form', __('form.errors.not_sent'));
            } elseif (RateLimiter::tooManyAttempts(self::limiterKey($this), Config::integer('ghm.public.submissions_per_hour'))) {
                $minutes = max(1, (int) ceil(RateLimiter::availableIn(self::limiterKey($this)) / 60));
                $validator->errors()->add('form', trans_choice('form.errors.too_many', $minutes, ['minutes' => $minutes]));
            }
        }];
    }

    /**
     * The rate-limit key for this visitor's submissions; hit once per accepted grievance.
     */
    public static function limiterKey(Request $request): string
    {
        return 'grievance-submissions:'.VisitorKey::for($request);
    }

    /**
     * The form carries the time it was shown, encrypted, so it cannot be forged and survives an
     * expired session. Missing, forged or too recent means a script, not a person.
     */
    private function sentTooSoon(): bool
    {
        try {
            $shownAt = (int) Crypt::decryptString($this->text('form_started'));
        } catch (DecryptException) {
            return true;
        }

        return now()->getTimestamp() - $shownAt < Config::integer('ghm.public.min_fill_seconds');
    }

    /**
     * A form field as text; a crafted list in its place reads as empty, so the usual message answers it.
     */
    private function text(string $key): string
    {
        $value = $this->input($key);

        return is_string($value) ? $value : '';
    }

    /**
     * The form sends strings: "1" or "0" for the anonymous choice, which the shared rules compare
     * as a boolean, and the incident date as day, month and year, the day possibly "unknown" (P5).
     * The Nepali form sends a Bikram Sambat date (U9), the English form an AD one; both are stored in AD.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_anonymous')) {
            $this->merge(['is_anonymous' => filter_var($this->input('is_anonymous'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)]);
        }

        // The server decides the calendar: only the Nepali form's own selects make a date BS, so a sent-in
        // "bs" can never mark a date outside the BS table, which the case page could not show.
        $this->merge(['incident_date_calendar' => 'ad']);

        $bs = $this->has('incident_bs_year');
        $prefix = $bs ? 'incident_bs_' : 'incident_';
        $day = $this->text($prefix.'day');
        $month = $this->integer($prefix.'month');
        $year = $this->integer($prefix.'year');

        if ($day !== '' && $month > 0 && $year > 0) {
            $exact = $day !== 'unknown';
            $day = $exact ? (int) $day : 1;
            // An impossible date stays impossible here (30 February, or 31 Kartik), so the date rule refuses it.
            $this->merge([
                'incident_date' => $bs ? self::fromBs($year, $month, $day) : sprintf('%04d-%02d-%02d', $year, $month, $day),
                'incident_date_exact' => $exact,
                'incident_date_calendar' => $bs ? 'bs' : 'ad',
            ]);
        }
    }

    /**
     * A BS date in AD, or a value the date rule refuses when the calendar has no such date.
     */
    private static function fromBs(int $year, int $month, int $day): string
    {
        try {
            return BsDate::toAd($year, $month, $day)->toDateString();
        } catch (InvalidArgumentException) {
            return 'no such date';
        }
    }

    /**
     * The fields every new case has (P4, P5). An anonymous case drops the
     * contact fields here, so they are never stored even if typed.
     *
     * @return array<string, mixed>
     */
    public static function caseRules(): array
    {
        return [
            'is_anonymous' => ['required', 'boolean'],
            'name' => ['exclude_if:is_anonymous,true', 'required', 'string', 'max:255'],
            // P4: a named complainant gives a phone or an email, also one who asks not to be contacted.
            'phone' => ['exclude_if:is_anonymous,true', 'nullable', 'required_if:preferred_contact,phone', 'required_without:email', 'string', 'max:30'],
            'email' => ['exclude_if:is_anonymous,true', 'nullable', 'required_if:preferred_contact,email', 'email', 'max:255'],
            'preferred_contact' => ['exclude_if:is_anonymous,true', 'required', Rule::enum(ContactMethod::class)],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            // False when only the month is remembered; the date then holds the first of that month.
            'incident_date_exact' => ['sometimes', 'boolean'],
            // Set above from the fields the form sent; offline entry leaves it to the column's "ad".
            'incident_date_calendar' => ['sometimes', Rule::in(['ad', 'bs'])],
            'recurrence' => ['required', Rule::enum(Recurrence::class)],
            'description' => ['required', 'string', 'max:5000'],
            // Optional (P6): blank or one of the question's codes, "prefer not to say" among them.
            ...array_map(fn (string $enum): array => ['nullable', Rule::enum($enum)], Grievance::OPTIONAL_ANSWERS),
        ];
    }
}
