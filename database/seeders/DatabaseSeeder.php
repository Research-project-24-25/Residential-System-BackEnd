<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Building;
use App\Models\Floor;
use App\Models\Apartment;
use App\Models\House;
use App\Models\Resident;

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

        // Create buildings with floors and apartments
        Building::factory(3)->create()->each(function ($building) {
            Floor::factory(rand(3, 6))->create([
                'building_id' => $building->id
            ])->each(function ($floor) {
                Apartment::factory(rand(2, 4))->create([
                    'floor_id' => $floor->id
                ]);
            });
        });

        // Create houses
        House::factory(10)->create();

        // Create residents (some in houses, some in apartments)
        Resident::factory(20)->create();
    }
}
