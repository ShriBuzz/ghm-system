<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Who is sending, for the public rate limits only: an HMAC of the IP address with a
 * random salt that is thrown away at midnight. The address itself is never stored, and once the
 * salt is gone no key can be turned back into it, not even with APP_KEY.
 */
class VisitorKey
{
    public static function for(Request $request): string
    {
        $day = now()->toDateString();
        $salt = Cache::remember("visitor-key-salt:{$day}", now()->endOfDay(), fn (): string => bin2hex(random_bytes(32)));

        return hash_hmac('sha256', (string) $request->ip(), $salt);
    }
}
