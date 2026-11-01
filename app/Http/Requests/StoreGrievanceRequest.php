<?php

namespace App\Http\Requests;

use App\Enums\ContactMethod;
use App\Enums\Recurrence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A new grievance from the public form (WP1). Offline entry (A6) uses the
 * same caseRules(), so a field change is made once for both.
 */
class StoreGrievanceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::caseRules();
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
            'recurrence' => ['required', Rule::enum(Recurrence::class)],
            'description' => ['required', 'string', 'max:5000'],
        ];
    }
}
