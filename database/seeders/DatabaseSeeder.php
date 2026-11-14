<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database: the first Administrator and the company's lists,
     * in production too. Both leave existing data alone, so seeding again is safe.
     */
    public function run(): void
    {
        $this->call(AdministratorSeeder::class);
        $this->call(CompanyListsSeeder::class);
    }
}
