<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * More information or a reply from the complainant, on the status page (P8).
 */
class ComplainantReplyRequest extends FormRequest
{
    protected $redirectRoute = 'status.show';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
