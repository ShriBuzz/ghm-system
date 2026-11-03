<?php

use App\Enums\Role;
use App\Filament\Pages\QrPoster;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Livewire\Livewire;

it('makes a QR code that reads back as the public form\'s address', function () {
    $read = (new QRCode(new QROptions(['readerUseImagickIfAvailable' => false])))->readFromBlob(QrPoster::png());

    expect((string) $read)->toBe(url('/'));
})->group('P10');

it('gives every role the poster, with the code to print or download and the address in words', function (Role $role) {
    app()->setLocale('en');
    signedInAs($role);

    $this->get('/admin')->assertOk()->assertSee(QrPoster::getUrl());
    Livewire::test(QrPoster::class)->assertOk()
        ->assertSee(url('/'))
        ->assertSee('src="data:image/svg+xml;base64,', false)
        ->assertSee('href="data:image/png;base64,', false)
        ->assertSee('गुनासो पठाउनुहोस्')->assertSee('Send a grievance');
})->with([Role::Administrator, Role::FocalPerson, Role::RestrictedOfficer])->group('P10', 'A1');
