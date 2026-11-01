<?php

namespace App\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, fn (Login $event) => activity()->causedBy($event->user instanceof Model ? $event->user : null)
            ->event('login')->log('Signed in'));

        // No email, IP or device: only the account, when the email belongs to one (S5, S6).
        Event::listen(Failed::class, fn (Failed $event) => activity()->causedBy($event->user instanceof Model ? $event->user : null)
            ->event('login_failed')->log('Failed sign-in'));
    }
}
