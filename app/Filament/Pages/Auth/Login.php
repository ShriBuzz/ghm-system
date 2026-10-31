<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use SensitiveParameter;

/**
 * Five failed sign-ins lock that email for 15 minutes. Every throttle is keyed
 * on a hash of the email, never the IP address. There is no "remember me", so
 * a session always ends after the idle lifetime.
 */
class Login extends BaseLogin
{
    private const MAX_FAILED_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 15 * 60;

    public function authenticate(): ?LoginResponse
    {
        $lockoutKey = 'login-lockout:'.$this->emailHash();

        if (RateLimiter::tooManyAttempts($lockoutKey, 1)) {
            $this->getRateLimitedNotification(
                new TooManyRequestsException(static::class, 'authenticate', null, RateLimiter::availableIn($lockoutKey)),
            )?->send();

            return null;
        }

        $response = parent::authenticate();

        if ($response) {
            RateLimiter::clear('login-failures:'.$this->emailHash());
        }

        return $response;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    protected function fireFailedEvent(Guard $guard, ?Authenticatable $user, #[SensitiveParameter] array $credentials): void
    {
        $failuresKey = 'login-failures:'.$this->emailHash();

        RateLimiter::hit($failuresKey, self::LOCKOUT_SECONDS);

        // The lockout runs 15 minutes from the fifth failure, not from the first.
        if (RateLimiter::attempts($failuresKey) >= self::MAX_FAILED_ATTEMPTS) {
            RateLimiter::clear($failuresKey);
            RateLimiter::hit('login-lockout:'.$this->emailHash(), self::LOCKOUT_SECONDS);
        }

        parent::fireFailedEvent($guard, $user, $credentials);
    }

    /**
     * Filament's own five-per-minute throttle, keyed on the email instead of the IP address.
     *
     * @param  string  $method
     * @param  class-string|null  $component
     */
    protected function getRateLimitKey($method, $component = null): string
    {
        return 'login-throttle:'.$this->emailHash();
    }

    private function emailHash(): string
    {
        return hash('sha256', Str::lower(trim((string) ($this->data['email'] ?? ''))));
    }
}
