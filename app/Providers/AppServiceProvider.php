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

        // Only for an existing account, and only the account: no email, IP or device (S5, S6). An unknown
        // email has no one to record, and the per-email throttle would not stop endless random ones.
        Event::listen(Failed::class, function (Failed $event): void {
            if ($event->user instanceof Model) {
                activity()->causedBy($event->user)->event('login_failed')->log('Failed sign-in');
            }
        });
    }
}
