<?php

namespace Database\Seeders;

use App\Enums\ROLE as ROLE_ENUM;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => ROLE_ENUM::PARTICIPANT->value]);
        Role::firstOrCreate(['name' => ROLE_ENUM::ORGANIZER->value]);
    }
}
