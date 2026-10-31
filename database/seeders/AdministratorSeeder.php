<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AdministratorSeeder extends Seeder
{
    /**
     * Create the first Administrator from the GHM_ADMIN_* values in ".env".
     */
    public function run(): void
    {
        $admin = Validator::make(config('ghm.admin'), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
        ], attributes: [
            'name' => 'GHM_ADMIN_NAME',
            'email' => 'GHM_ADMIN_EMAIL',
            'password' => 'GHM_ADMIN_PASSWORD',
        ])->validate();

        User::firstOrCreate(['email' => $admin['email']], [
            'name' => $admin['name'],
            'password' => $admin['password'],
            'role' => Role::Administrator,
        ]);
    }
}
