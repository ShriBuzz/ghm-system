<?php

use App\Models\User;
use Filament\Auth\MultiFactor\App\AppAuthentication;

it('draws staff avatars in the page instead of sending names to ui-avatars.com', function () {
    $user = User::factory()->create([
        'name' => 'Test Officer',
        'app_authentication_secret' => AppAuthentication::make()->generateSecret(),
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('ui-avatars.com');

    expect(filament()->getUserAvatarUrl($user))
        ->toBe('data:image/svg+xml;base64,'.base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#030712"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="sans-serif" font-size="26" fill="#fff">TO</text></svg>'
        ));
})->group('A1');
