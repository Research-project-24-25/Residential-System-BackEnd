<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Resident;
use App\Models\Property;
use App\Models\PropertyResident;
use App\Models\User;
use App\Models\Service;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\MeetingRequest;
use App\Models\Notification;
use App\Models\Maintenance;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceFeedback;

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

        // Create test user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'user@example.com',
        ]);

        // Create regular users
        User::factory(15)->create();

        // Create properties 
        $properties = Property::factory(50)->create();

        // Create test resident
        $resident = Resident::factory()->create([
            'username' => 'resident',
            'email' => 'resident@example.com',
            'password' => bcrypt('password'),
            'created_by' => Admin::first()->id,
        ]);

        // Create services
        $services = Service::factory(15)->create();

        // Create regular residents
        $residents = Resident::factory(25)->create();

        // Create property-resident relationships
        foreach ($residents as $resident) {
            $randomProperties = $properties->random(rand(1, 2));
            foreach ($randomProperties as $property) {
                PropertyResident::factory()->create([
                    'resident_id' => $resident->id,
                    'property_id' => $property->id
                ]);
            }
        }

        // Create property-service relationships
        foreach ($properties as $property) {
            // Assign 2-4 random services to each property
            $randomServices = $services->random(rand(2, 4));
            foreach ($randomServices as $service) {
                $property->services()->attach($service->id, [
                    'billing_type' => ['fixed', 'area_based', 'prepaid'][array_rand(['fixed', 'area_based', 'prepaid'])],
                    'price' => fake()->randomFloat(2, 50, 1000),
                    'status' => ['active', 'inactive'][array_rand(['active', 'inactive'])],
                    'details' => json_encode([
                        'notes' => fake()->sentence(),
                        'terms' => fake()->paragraph()
                    ]),
                    'activated_at' => fake()->optional(0.7)->dateTimeBetween('-1 year', 'now'),
                    'expires_at' => fake()->optional(0.5)->dateTimeBetween('+1 month', '+2 years'),
                    'last_billed_at' => fake()->optional(0.6)->dateTimeBetween('-6 months', 'now'),
                ]);
            }
        }

        // Create bills for residents
        $bills = [];
        foreach ($residents as $resident) {
            $bills = array_merge(
                $bills,
                Bill::factory()
                    ->count(rand(1, 5))
                    ->create(['resident_id' => $resident->id])
                    ->toArray()
            );
        }

        // Create payments for bills
        foreach ($bills as $bill) {
            if (rand(0, 1)) { // 50% chance of having a payment
                Payment::factory()->create([
                    'bill_id' => $bill['id'],
                    'resident_id' => $bill['resident_id']
                ]);
            }
        }

        // Create meeting requests
        MeetingRequest::factory(20)->create();

        // Create notifications
        Notification::factory(100)->create([
            'notifiable_type' => User::class,
            'notifiable_id' => 1,
        ]);

        // Create maintenance types
        Maintenance::factory(10)->create();

        // Create maintenance requests
        $maintenanceRequests = MaintenanceRequest::factory(30)->create();

        // Create feedback for some completed maintenance requests
        $completedRequests = MaintenanceRequest::where('status', 'completed')
            ->where('has_feedback', true)
            ->get();

        foreach ($completedRequests as $request) {
            MaintenanceFeedback::factory()->create([
                'maintenance_request_id' => $request->id,
                'resident_id' => $request->resident_id,
            ]);
        }
    }
}
