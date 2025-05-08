<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;
use App\Models\Resident;
use App\Models\Property;
use App\Models\PropertyResident;
use App\Models\User;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\PaymentMethod;
use App\Models\Bill;
use App\Models\Payment;
use App\Models\MeetingRequest;
use App\Models\Notification;

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

        // Create payment methods
        foreach ($residents as $resident) {
            PaymentMethod::factory()
                ->count(rand(1, 3))
                ->create(['resident_id' => $resident->id]);
        }

        // Create services
        Service::factory(15)->create();

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
                    'resident_id' => $bill['resident_id'],
                    'payment_method_id' => PaymentMethod::where('resident_id', $bill['resident_id'])->inRandomOrder()->first()->id,
                ]);
            }
        }

        // Create meeting requests
        MeetingRequest::factory(20)->create();

        // Create service requests
        ServiceRequest::factory(30)->create();

        // Create notifications
        Notification::factory(100)->create([
            'notifiable_type' => User::class,
            'notifiable_id' => 1,
        ]);
    }
}
