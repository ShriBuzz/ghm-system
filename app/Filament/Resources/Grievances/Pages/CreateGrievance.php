<?php

namespace App\Filament\Resources\Grievances\Pages;

use App\Enums\Channel;
use App\Enums\Status;
use App\Filament\Resources\Grievances\GrievanceResource;
use App\Http\Requests\StoreGrievanceRequest;
use App\Models\Grievance;
use App\Support\Pin;
use App\Support\ReferenceNumber;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Staff enter a grievance received offline (A6). It gets a reference and PIN
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

        return DB::transaction(function () use ($valid): Grievance {
            $pin = Pin::generate();
            $case = new Grievance;
            $case->forceFill($valid + [
                'reference' => ReferenceNumber::next(),
                'pin_hash' => Hash::make($pin),
                'status' => Status::Received,
                'locale' => 'ne',
                'created_by' => auth()->id(),
            ])->save();

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
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            // Filament shows errors under its "data." state path.
            throw ValidationException::withMessages(collect($validator->errors()->messages())->mapWithKeys(fn ($messages, $field) => ["data.$field" => $messages])->all());
        }

        return $validator->validated();
    }
}
