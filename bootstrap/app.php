<?php

use App\Http\Middleware\SecurityHeaders;
use App\Support\FailedJobStore;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Exception\RfcComplianceException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Mail servers, and the mailer itself, quote the recipient's address when they refuse an email; it never reaches the log.
        $exceptions->map(TransportExceptionInterface::class, fn (TransportExceptionInterface $e) => FailedJobStore::withoutAddress($e));
        $exceptions->map(RfcComplianceException::class, fn (RfcComplianceException $e) => FailedJobStore::withoutAddress($e));

        // A public form sent after the session ended (30 idle minutes) fails its CSRF check, which Laravel
        // turns into a 419 before this runs. Send the visitor back to the form by name, with what they typed,
        // instead of "Page expired": no referrer is sent and the old session is gone, so "back" knows nothing.
        $exceptions->render(function (HttpException $e, Request $request) {
            $form = ['grievance.store' => 'home', 'status.check' => 'status.lookup', 'status.reply' => 'status.lookup'][$request->route()?->getName()] ?? null;

            if ($e->getStatusCode() !== 419 || $form === null) {
                return null;
            }

            // Only a browser's own post is refilled. A current browser's own post never gets here (its Sec-Fetch-Site
            // passes the check), so one with the header came from another site; an older browser may name it in Origin.
            // Refilling that would let another site plant a named form with its own email, where the PIN is sent (P7).
            $origin = $request->headers->get('Origin');
            if ($request->headers->has('Sec-Fetch-Site') || ($origin !== null && $origin !== $request->getSchemeAndHttpHost())) {
                return null;
            }

            // The CSRF check runs before SetLocale, so take the language from the address.
            app()->setLocale((string) $request->route('locale'));

            return redirect()->route($form, ['locale' => $request->route('locale')])
                ->withInput($request->except(['_token', 'attachment', 'website', 'pin', 'body']))
                ->withErrors([$request->routeIs('grievance.store') ? 'form' : 'lookup' => __($request->routeIs('grievance.store') ? 'form.errors.expired' : 'track.expired')]);
        });

        // A PIN typed on the status page never comes back in a refused form's old input.
        $exceptions->dontFlash(['pin']);
    })->create();
