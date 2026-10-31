<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\AdministratorSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    config(['ghm.admin' => [
        'name' => 'Test Administrator',
        'email' => 'admin@example.com',
        'password' => 'a-long-test-password',
    ]]);
});

it('creates one Administrator from the GHM_ADMIN settings', function () {
    $this->seed(AdministratorSeeder::class);
    $this->seed(AdministratorSeeder::class);

    $admin = User::sole();

    expect($admin->email)->toBe('admin@example.com')
        ->and($admin->role)->toBe(Role::Administrator)
        ->and(Hash::check('a-long-test-password', $admin->password))->toBeTrue();
})->group('A1');

it('refuses to run when a GHM_ADMIN setting is empty', function (string $key) {
    config(["ghm.admin.$key" => '']);

    expect(fn () => $this->seed(AdministratorSeeder::class))->toThrow(ValidationException::class)
        ->and(User::count())->toBe(0);
})->with(['name', 'email', 'password'])->group('A1');
