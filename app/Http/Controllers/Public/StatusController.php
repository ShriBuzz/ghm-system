<?php

namespace App\Http\Controllers\Public;

use App\Enums\MessageDirection;
use App\Http\Requests\ComplainantReplyRequest;
use App\Http\Requests\StatusLookupRequest;
use App\Models\Grievance;
use App\Support\VisitorKey;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * The status page (P8): look a grievance up by reference and PIN, read the status and the company's
 * replies, and add information. Staff visibility rules do not apply: the PIN is the complainant's key.
 */
class StatusController
{
    private const MAX_WRONG_PINS = 5;

    private const LOCK_SECONDS = 15 * 60;

    private const IDLE_SECONDS = 10 * 60;

    public function lookup(): View
    {
        return view('public.status-lookup');
    }

    public function check(StatusLookupRequest $request): RedirectResponse
    {
        $reference = Str::upper(trim($request->string('reference')->toString()));
        $visitor = 'status-failures:'.VisitorKey::for($request);
        $wrongPins = 'status-wrong-pins:'.hash('sha256', $reference);
        $lock = 'status-lock:'.hash('sha256', $reference);

        // Per visitor, so a bot cannot spread guesses over every reference; per reference, 5 wrong PINs
        // lock it for 15 minutes from the fifth. An unknown reference counts and answers the same.
        if (RateLimiter::tooManyAttempts($visitor, Config::integer('ghm.public.failed_lookups_per_hour'))) {
            return $this->refuse($reference, __('track.errors.too_many'));
        }
        if (RateLimiter::tooManyAttempts($lock, 1)) {
            return $this->refuse($reference, __('track.errors.locked'));
        }

        $case = Grievance::where('reference', $reference)->first();
        // The same hash check for an unknown reference, so its timing does not tell which references exist.
        $valid = Hash::check($request->string('pin')->toString(), $case->pin_hash ?? self::dummyHash());

        if (! $case || ! $valid) {
            RateLimiter::hit($visitor, 3600);
            RateLimiter::hit($wrongPins, self::LOCK_SECONDS);

            if (RateLimiter::attempts($wrongPins) >= self::MAX_WRONG_PINS) {
                RateLimiter::clear($wrongPins);
                RateLimiter::hit($lock, self::LOCK_SECONDS);

                return $this->refuse($reference, __('track.errors.locked'));
            }

            return $this->refuse($reference, __('track.errors.wrong'));
        }

        RateLimiter::clear($wrongPins);
        $request->session()->regenerate();
        $request->session()->put('status', ['case' => $case->id, 'seen' => now()->getTimestamp()]);

        return redirect()->route('status.show');
    }

    public function show(Request $request): Response|RedirectResponse
    {
        $case = $this->openCase($request);

        if (! $case) {
            return $this->expired();
        }

        return response()->view('public.status', [
            'case' => $case,
            // Messages only: internal notes are never loaded on a public page.
            'messages' => $case->messages()->oldest()->oldest('id')->get(),
            'draft' => $request->session()->pull('status_draft'),
        ])->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex');
    }

    public function reply(ComplainantReplyRequest $request): RedirectResponse
    {
        $case = $this->openCase($request);

        if (! $case) {
            // Kept for the next lookup, so a reply typed while the page sat open is not lost.
            $request->session()->put('status_draft', $request->validated('body'));

            return $this->expired();
        }

        // Its created hook alerts the staff who can see the case (N1).
        $case->messages()->create(['direction' => MessageDirection::Complainant, 'body' => $request->validated('body')]);

        return redirect()->route('status.show')->with('status_notice', __('track.sent'));
    }

    public function close(Request $request): RedirectResponse
    {
        $request->session()->forget(['status', 'status_draft']);

        return redirect()->route('status.lookup')->with('status_notice', __('track.closed'));
    }

    /**
     * The case this visitor opened with its PIN, while they keep using it; after 10 idle minutes they
     * enter the PIN again, so a phone left lying around does not keep the grievance open.
     */
    private function openCase(Request $request): ?Grievance
    {
        $open = $request->session()->get('status');

        if (! is_array($open) || now()->getTimestamp() - (int) $open['seen'] > self::IDLE_SECONDS) {
            $request->session()->forget('status');

            return null;
        }

        $request->session()->put('status.seen', now()->getTimestamp());

        return Grievance::whereKey($open['case'])->first();
    }

    private function expired(): RedirectResponse
    {
        return redirect()->route('status.lookup')->with('status_notice', __('track.expired'));
    }

    private function refuse(string $reference, string $message): RedirectResponse
    {
        return redirect()->route('status.lookup')->withInput(['reference' => $reference])->withErrors(['lookup' => $message]);
    }

    /**
     * A hash of nothing, made once with this server's cost, for the unknown-reference check.
     */
    private static function dummyHash(): string
    {
        return Cache::rememberForever('status-lookup-dummy-hash', fn (): string => Hash::make(Str::random(40)));
    }
}
