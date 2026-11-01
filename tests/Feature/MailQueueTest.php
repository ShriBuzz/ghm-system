<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Exception\TransportException;

it('works the mail queue from cron every minute, one worker at a time', function () {
    $worker = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, 'queue:work'));

    expect($worker)->not->toBeNull()
        ->and($worker->command)->toContain('--stop-when-empty')
        ->and($worker->expression)->toBe('* * * * *')
        ->and($worker->withoutOverlapping)->toBeTrue();
})->group('N1', 'N2');

it('logs a refused email without the address the mail server quoted', function () {
    Log::spy();

    report(new TransportException('Expected response code "250" but got code "550", with message "550 5.1.1 <complainant@example.com>: Recipient address rejected".', 550));

    Log::shouldHaveReceived('error')->once()->withArgs(fn (string $message) => str_contains($message, '550')
        && ! str_contains($message, 'complainant@example.com'));
})->group('N1', 'N2', 'S5');

it('deletes failed jobs after 7 days, since their error text may quote an address', function () {
    $prune = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, 'queue:prune-failed'));

    expect($prune)->not->toBeNull()
        ->and($prune->command)->toContain('--hours=168')
        ->and($prune->expression)->toBe('0 0 * * *');
})->group('N1', 'N2', 'S5');
