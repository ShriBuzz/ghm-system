<?php

use App\Models\Grievance;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('counts dates in Nepal time', function () {
    expect(now()->timezoneName)->toBe('Asia/Kathmandu');
})->group('A2');

it('stores times in Nepal time whatever the database server\'s own time zone, so a move to another host shifts none', function () {
    // MySQL reads and writes TIMESTAMP columns in the connection's time zone, which is the server's own unless set.
    expect(DB::scalar('select @@session.time_zone'))->toBe('+05:45');

    $case = Grievance::factory()->create(['received_at' => '2026-11-13 10:00:00']);
    expect((int) DB::scalar('select unix_timestamp(received_at) from grievances where id = ?', [$case->id]))
        ->toBe(CarbonImmutable::parse('2026-11-13 10:00:00', 'Asia/Kathmandu')->getTimestamp());
})->group('A2', 'S7');
