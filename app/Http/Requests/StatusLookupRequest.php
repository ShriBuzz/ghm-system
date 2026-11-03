<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A reference number and PIN typed on the status page (P8).
 */
class StatusLookupRequest extends FormRequest
{
    protected $redirectRoute = 'status.lookup';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:20'],
            'pin' => ['required', 'string', 'max:20'],
        ];
    }
}
