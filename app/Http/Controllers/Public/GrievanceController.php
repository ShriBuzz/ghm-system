<?php

namespace App\Http\Controllers\Public;

use App\Enums\Channel;
use App\Enums\Status;
use App\Http\Requests\StoreGrievanceRequest;
use App\Models\Category;
use App\Models\Grievance;
use App\Models\Location;
use App\Models\Setting;
use App\Support\AttachmentStore;
use App\Support\Pin;
use App\Support\ReferenceNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * The public grievance form and the page that hands out the reference and PIN.
 */
class GrievanceController
{
    public function create(): View
    {
        $locale = app()->getLocale();
        $name = "name_{$locale}";
        $notice = Setting::value("notice.{$locale}") ?: __('notice.default');

        return view('public.form', [
            // By character (u): without it, \R also matches a byte inside some Nepali letters, such as अ.
            'notice' => preg_split('/\R{2,}/u', trim($notice)),
            'categories' => Category::where('is_active', true)->orderBy('sort')->orderBy($name)->pluck($name, 'id'),
            'locations' => Location::where('is_active', true)->orderBy('sort')->orderBy($name)->pluck($name, 'id'),
            // When the form was shown, for the spam check. After a refusal or an expired session, the
            // first showing still counts.
            'started' => old('form_started') ?: Crypt::encryptString((string) now()->getTimestamp()),
        ]);
    }

    public function store(StoreGrievanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('attachment');

        // The PIN's slow hash before the transaction: the reference counter's row stays locked until the commit,
        // and every other submission waits for it (P7).
        $pin = Pin::generate();
        $pinHash = Hash::make($pin);

        $case = DB::transaction(function () use ($data, $file, $pinHash): Grievance {
            $case = new Grievance;
            $case->forceFill(Arr::except($data, ['attachment']) + [
                'reference' => ReferenceNumber::next(),
                'pin_hash' => $pinHash,
                'channel' => Channel::Web,
                'status' => Status::Received,
                'locale' => app()->getLocale(),
                'received_at' => now(),
            ])->save();

            if ($file instanceof UploadedFile) {
                AttachmentStore::store($file, $case, field: 'attachment');
            }

            return $case;
        });

        $case->acknowledge($pin);
        RateLimiter::hit(StoreGrievanceRequest::limiterKey($request), 3600);

        // Until Done or the session ends, so a reload on the phone does not lose the PIN. Sessions are encrypted.
        session()->put('received', ['reference' => $case->reference, 'pin' => $pin, 'emailed' => filled($case->email)]);

        return redirect()->route('grievance.received');
    }

    public function received(): Response|RedirectResponse
    {
        $received = session('received');

        if (! is_array($received)) {
            return redirect()->route('home');
        }

        // The PIN is on this page: no browser or proxy keeps a copy, and no search engine lists it.
        return response()->view('public.received', $received)
            ->header('Cache-Control', 'no-store, private')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function done(): RedirectResponse
    {
        session()->forget('received');

        return redirect()->route('home');
    }
}
