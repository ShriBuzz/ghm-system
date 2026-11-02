<?php

use App\Enums\Role;
use App\Filament\Pages\PublicNotice;
use App\Models\Setting;
use Livewire\Livewire;

it('lets the Administrator change the notice above the form, in both languages', function () {
    signedInAs(Role::Administrator);

    $this->get('/admin')->assertOk()->assertSee(PublicNotice::getUrl());
    Livewire::test(PublicNotice::class)
        ->fillForm(['ne' => 'गोप्य राखिन्छ।', 'en' => 'Kept confidential.'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Setting::value('notice.ne'))->toBe('गोप्य राखिन्छ।')
        ->and(Setting::value('notice.en'))->toBe('Kept confidential.');

    // The page opens with what is saved.
    Livewire::test(PublicNotice::class)->assertSchemaStateSet(['ne' => 'गोप्य राखिन्छ।', 'en' => 'Kept confidential.']);
})->group('P3', 'A7');

it('needs the notice in both languages', function () {
    signedInAs(Role::Administrator);

    Livewire::test(PublicNotice::class)
        ->fillForm(['ne' => 'गोप्य राखिन्छ।', 'en' => ''])
        ->call('save')
        ->assertHasFormErrors(['en' => 'required']);

    expect(Setting::value('notice.ne'))->toBeNull();
})->group('P3', 'A7');

it('keeps the notice to the Administrator', function (Role $role) {
    signedInAs($role);

    $this->get('/admin')->assertOk()->assertDontSee(PublicNotice::getUrl());
    $this->get(PublicNotice::getUrl())->assertForbidden();
})->with([Role::FocalPerson, Role::RestrictedOfficer])->group('P3', 'A7');
