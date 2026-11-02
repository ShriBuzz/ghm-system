<?php

namespace App\Http\Requests;

use App\Enums\ContactMethod;
use App\Enums\Recurrence;
use App\Models\Grievance;
use App\Support\AttachmentStore;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
     * The form sends strings: "1" or "0" for the anonymous choice, which the shared rules compare
     * as a boolean, and the incident date as day, month and year, the day possibly "unknown" (P5).
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('is_anonymous')) {
            $this->merge(['is_anonymous' => filter_var($this->input('is_anonymous'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)]);
        }

        $day = $this->string('incident_day')->toString();
        $month = $this->integer('incident_month');
        $year = $this->integer('incident_year');

        if ($day !== '' && $month > 0 && $year > 0) {
            $exact = $day !== 'unknown';
            // An impossible day such as 30 February stays impossible here, so the date rule refuses it.
            $this->merge([
                'incident_date' => sprintf('%04d-%02d-%02d', $year, $month, $exact ? (int) $day : 1),
                'incident_date_exact' => $exact,
            ]);
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
            // The preferred method is required and is phone or email, so at least one is given.
            'phone' => ['exclude_if:is_anonymous,true', 'nullable', 'required_if:preferred_contact,phone', 'string', 'max:30'],
            'email' => ['exclude_if:is_anonymous,true', 'nullable', 'required_if:preferred_contact,email', 'email', 'max:255'],
            'preferred_contact' => ['exclude_if:is_anonymous,true', 'required', Rule::enum(ContactMethod::class)],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'incident_date' => ['required', 'date', 'before_or_equal:today'],
            // False when only the month is remembered; the date then holds the first of that month.
            'incident_date_exact' => ['sometimes', 'boolean'],
            'recurrence' => ['required', Rule::enum(Recurrence::class)],
            'description' => ['required', 'string', 'max:5000'],
            // Optional (P6): blank or one of the question's codes, "prefer not to say" among them.
            ...array_map(fn (string $enum): array => ['nullable', Rule::enum($enum)], Grievance::OPTIONAL_ANSWERS),
        ];
    }
}
