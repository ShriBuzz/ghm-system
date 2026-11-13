<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * A reference number and PIN typed on the status page.
 */
class StatusLookupRequest extends FormRequest
{
    protected $redirectRoute = 'status.lookup';

    /**
     * As people type them: Nepali digits, spaces, small letters, a reference without hyphens. Before the
     * lookup, so each variant counts against the one reference's wrong-PIN lock.
     */
    protected function prepareForValidation(): void
    {
        $plain = fn (mixed $value): mixed => is_string($value)
            ? preg_replace('/\s+/u', '', strtr($value, array_combine(['०', '१', '२', '३', '४', '५', '६', '७', '८', '९'], range(0, 9))))
            : $value;
        $reference = $plain($this->input('reference'));

        $this->merge([
            'reference' => is_string($reference) ? (string) preg_replace('/^GHM-?(\d{4})-?(\d+)$/', 'GHM-$1-$2', Str::upper($reference)) : $reference,
            'pin' => $plain($this->input('pin')),
        ]);
    }

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
