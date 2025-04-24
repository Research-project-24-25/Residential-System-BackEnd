<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Resident;
use App\Models\Property;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a super admin
        Admin::factory()->create([
            'email' => 'super@admin.com',
            'username' => 'superadmin',
            'role' => 'super_admin'
        ]);

        // Create regular admin
        Admin::factory()->create([
            'email' => 'admin@admin.com',
            'username' => 'admin',
            'role' => 'admin'
        ]);

        // Create additional admins
        Admin::factory(3)->create([
            'role' => 'admin'
        ]);

        // Create properties 
        Property::factory(100)->create();

        // Create residents
        Resident::factory(20)->create();
    }
}
