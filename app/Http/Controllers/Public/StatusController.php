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
 * The status page: look a grievance up by reference and PIN, read the status and the company's
 * replies, and add information. Staff visibility rules do not apply: the PIN is the complainant's key.
 */
class StatusController
{
    private const MAX_WRONG_PINS = 5;

    private const LOCK_SECONDS = 15 * 60;

    private const IDLE_SECONDS = 10 * 60;

    /** Each complainant message alerts staff by email, so one case cannot flood the mail server. */
    private const MESSAGES_PER_HOUR = 10;

    public function lookup(): View
    {
        return view('public.status-lookup');
    }

    public function check(StatusLookupRequest $request): RedirectResponse
    {
        $reference = $request->string('reference')->toString();
        $case = Grievance::where('reference', $reference)->first();
        // The stored reference when there is one, so every spelling the database reads as it (full-width
        // characters) counts against the one lock.
        $counted = hash('sha256', $case->reference ?? $reference);
        $visitor = 'status-failures:'.VisitorKey::for($request);
        $wrongPins = 'status-wrong-pins:'.$counted;
        $lock = 'status-lock:'.$counted;

        // Per visitor, so a bot cannot spread guesses over every reference; per reference, 5 wrong PINs
        // lock it for 15 minutes from the fifth. An unknown reference counts and answers the same.
        if (RateLimiter::tooManyAttempts($visitor, Config::integer('ghm.public.failed_lookups_per_hour'))) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($visitor) / 60));

            return $this->refuse($reference, trans_choice('track.errors.too_many', $minutes, ['minutes' => $minutes]));
        }
        if (RateLimiter::tooManyAttempts($lock, 1)) {
            return $this->refuse($reference, __('track.errors.locked'));
        }

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
            'draft' => $this->draftFor($request, $case),
        ])->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex');
    }

    public function reply(ComplainantReplyRequest $request): RedirectResponse
    {
        $opened = $request->session()->get('status.case');
        $case = $this->openCase($request);

        if (! $case) {
            // Kept for the next lookup of this same grievance only: on a shared phone, someone else may open theirs next.
            if ($opened !== null) {
                $request->session()->put('status_draft', ['case' => (int) $opened, 'body' => $request->validated('body')]);
            }

            return $this->expired();
        }

        $limit = "status-messages:{$case->id}";
        if (RateLimiter::tooManyAttempts($limit, self::MESSAGES_PER_HOUR)) {
            return redirect()->route('status.show')->withInput()->withErrors(['body' => __('track.errors.too_many_messages')]);
        }

        // Its created hook alerts the staff who can see the case.
        $case->messages()->create(['direction' => MessageDirection::Complainant, 'body' => $request->validated('body')]);
        RateLimiter::hit($limit, 3600);

        return redirect()->route('status.show')->with('status_notice', __('track.sent'));
    }

    public function close(Request $request): RedirectResponse
    {
        // The page with the reference and PIN too, so "Hidden on this phone" holds for both.
        $request->session()->forget(['status', 'status_draft', 'received']);

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

    /**
     * A reply typed after access expired, only for the grievance it was written for.
     */
    private function draftFor(Request $request, Grievance $case): ?string
    {
        $draft = $request->session()->get('status_draft');

        if (! is_array($draft) || $draft['case'] !== $case->id) {
            return null;
        }

        $request->session()->forget('status_draft');

        return $draft['body'];
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
