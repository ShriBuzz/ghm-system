<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;

it('deletes expired cache entries, which the file cache would otherwise keep for good', function () {
    // Sign-in counters live here: one per email tried, read again only if that email comes back.
    $cache = Cache::store('file');
    $cache->put('prune-test-expired', 'x', 60);
    $cache->put('prune-test-current', 'x', 3600);
    $cache->forever('prune-test-forever', 'x');
    $file = fn (string $key): string => $cache->getStore()->path($key);

    $this->travel(2)->minutes();

    try {
        $this->artisan('ghm:prune-cache')->assertSuccessful();

        expect(file_exists($file('prune-test-expired')))->toBeFalse()
            ->and(file_exists($file('prune-test-current')))->toBeTrue()
            ->and(file_exists($file('prune-test-forever')))->toBeTrue();
    } finally {
        $cache->forget('prune-test-expired');
        $cache->forget('prune-test-current');
        $cache->forget('prune-test-forever');
    }
})->group('S2', 'S5');

it('prunes the cache every night', function () {
    $prune = collect(app(Schedule::class)->events())
        ->first(fn (Event $event) => str_contains((string) $event->command, 'ghm:prune-cache'));

    expect($prune?->expression)->toBe('15 3 * * *');
})->group('S2', 'S5');
