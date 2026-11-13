<?php

namespace App\Filament\Resources\Grievances\Pages;

use App\Enums\Channel;
use App\Enums\Status;
use App\Filament\Resources\Grievances\GrievanceResource;
use App\Http\Middleware\SetLocale;
use App\Http\Requests\StoreGrievanceRequest;
use App\Models\Grievance;
use App\Support\Pin;
use App\Support\ReferenceNumber;
use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Staff enter a grievance received offline. It gets a reference and PIN
 * like a web case; the PIN is shown once on the next page and stored only hashed.
 */
class CreateGrievance extends CreateRecord
{
    protected static string $resource = GrievanceResource::class;

    protected static ?string $title = 'Enter offline case';

    // Off: "create another" would skip the slip, and the PIN would never be shown.
    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Grievance
    {
        $valid = $this->validateCase($data);

        // The PIN's slow hash before the transaction: the reference counter's row stays locked until the commit,
        // and every other submission waits for it (P7).
        $pin = Pin::generate();
        $pinHash = Hash::make($pin);

        return DB::transaction(function () use ($valid, $pin, $pinHash): Grievance {
            $case = new Grievance;
            $case->forceFill($valid + [
                'reference' => ReferenceNumber::next(),
                'pin_hash' => $pinHash,
                'status' => Status::Received,
                'created_by' => auth()->id(),
            ])->save();
            $case->acknowledge($pin);

            // One request only: the slip page reads it once, and it is never stored on the case.
            session()->flash('recorded_case', [
                'reference' => $case->reference,
                'pin' => $pin,
                'received_at' => $case->received_at->toDateTimeString(),
            ]);

            return $case;
        });
    }

    protected function getRedirectUrl(): string
    {
        return RecordedCase::getUrl();
    }

    protected function getCreatedNotification(): null
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validateCase(array $data): array
    {
        $rules = StoreGrievanceRequest::caseRules() + [
            'channel' => ['required', Rule::enum(Channel::class)->except([Channel::Web])],
            'received_at' => ['required', 'date', 'before_or_equal:now'],
            'locale' => ['required', Rule::in(SetLocale::LOCALES)],
        ];

        // Filament's selects hold enum objects; the shared rules compare codes, as the public form sends them.
        $data = array_map(fn ($value) => $value instanceof BackedEnum ? $value->value : $value, $data);
        $data['incident_date_exact'] = ! ($data['day_unknown'] ?? false);
        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            // Filament shows errors under its "data." state path.
            throw ValidationException::withMessages(collect($validator->errors()->messages())->mapWithKeys(fn ($messages, $field) => ["data.$field" => $messages])->all());
        }

        $valid = $validator->validated();
        // The day picked stands for its month, as the public form's "I don't remember the day".
        if (! $valid['incident_date_exact']) {
            $valid['incident_date'] = CarbonImmutable::parse($valid['incident_date'])->startOfMonth()->toDateString();
        }

        return $valid;
    }
}
