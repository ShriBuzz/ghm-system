<?php

use App\Enums\Priority;
use App\Enums\Status;

it('lists the statuses exactly as RFQ A4 does', function () {
    app()->setLocale('en');

    expect(array_map(fn (Status $status) => $status->getLabel(), Status::cases()))->toBe([
        'Received', 'Under assessment', 'Under investigation', 'Referred / Out of scope', 'Resolved', 'Closed', 'Reopened',
    ]);
})->group('A4');

it('lists the priorities exactly as RFQ A4 does', function () {
    app()->setLocale('en');

    expect(array_map(fn (Priority $priority) => $priority->getLabel(), Priority::cases()))->toBe([
        'Low', 'Medium', 'High', 'Critical',
    ]);
})->group('A4');

it('uses the company\'s Nepali labels for statuses and priorities', function () {
    app()->setLocale('ne');

    expect(array_map(fn (Status $status) => $status->getLabel(), Status::cases()))->toBe([
        'प्राप्त', 'प्रारम्भिक मूल्याङ्कनको क्रममा', 'छानबिनको क्रममा', 'अन्यत्र सिफारिश गरिएको / दायराबाहिर',
        'समाधान भएको', 'बन्द गरिएको', 'पुनः खोलिएको',
    ])->and(array_map(fn (Priority $priority) => $priority->getLabel(), Priority::cases()))->toBe([
        'न्यून', 'मध्यम', 'उच्च', 'अति गम्भीर',
    ]);
})->group('A4', 'U8');

it('labels every status and priority in Nepali', function (Status|Priority $case) {
    app()->setLocale('en');
    $english = $case->getLabel();

    app()->setLocale('ne');

    expect($case->getLabel())->not->toBe($english)
        ->and($case->getLabel())->toMatch('/\p{Devanagari}/u');
})->with([...Status::cases(), ...Priority::cases()])->group('A4');
