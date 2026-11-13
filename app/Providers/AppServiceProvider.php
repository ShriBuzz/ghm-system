<?php

namespace App\Providers;

use App\Support\FailedJobStore;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Queue\Failed\DatabaseUuidFailedJobProvider;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Failed jobs keep their error without a refused email's address. Laravel's own
        // store for the configured driver is swapped for the same store with that one change.
        $this->app->extend('queue.failer', fn (FailedJobProviderInterface $failer, Application $app) => $failer instanceof DatabaseUuidFailedJobProvider
            ? new FailedJobStore($app->make('db'), config('queue.failed.database'), config('queue.failed.table'))
            : $failer);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // `php artisan about` is the first check after a deploy (docs/deploy.md). Without the password
        // the nightly archive is made unencrypted, and nothing else would say so.
        AboutCommand::add('Backups', fn (): array => [
            'Archive password' => filled(config('backup.backup.password')) ? 'set' : '<fg=red;options=bold>NOT SET: archives are not encrypted</>',
        ]);

        Event::listen(Login::class, fn (Login $event) => activity()->causedBy($event->user instanceof Model ? $event->user : null)
            ->event('login')->log('Signed in'));

        // Only for an existing account, and only the account: no email, IP or device. An unknown
        // email has no one to record, and the per-email throttle would not stop endless random ones.
        Event::listen(Failed::class, function (Failed $event): void {
            if ($event->user instanceof Model) {
                activity()->causedBy($event->user)->event('login_failed')->log('Failed sign-in');
            }
        });
    }
}
