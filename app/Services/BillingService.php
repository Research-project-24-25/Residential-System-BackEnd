<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Property;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class BillingService
{
    /**
     * Create a new bill record
     *
     * @param array $data
     * @return Bill
     */
    public function createBill(array $data): Bill
    {
        return DB::transaction(function () use ($data) {
            $bill = Bill::create($data);

            // Update the bill status based on due date
            $this->updateBillStatus($bill);

            // Notify the resident about the new bill
            $bill->resident->notify(new \App\Notifications\NewBillNotification($bill));

            return $bill;
        });
    }

    /**
     * Update an existing bill
     *
     * @param Bill $bill
     * @param array $data
     * @return Bill
     */
    public function updateBill(Bill $bill, array $data): Bill
    {
        return DB::transaction(function () use ($bill, $data) {
            $bill->update($data);

            // Update the bill status based on due date and payments
            $this->updateBillStatus($bill);

            return $bill;
        });
    }

    /**
     * Update bill status based on due date and payments
     *
     * @param Bill $bill
     * @return void
     */
    private function updateBillStatus(Bill $bill): void
    {
        // Force refresh to get latest payment data
        $bill->refresh();

        // If fully paid, mark as paid
        if ($bill->is_fully_paid) {
            $bill->update(['status' => 'paid']);
            return;
        }

        // If due date has passed and not fully paid, mark as overdue
        if ($bill->due_date < now() && !$bill->is_fully_paid) {
            // Check if the status is changing to overdue to avoid sending duplicate notifications
            if ($bill->status !== 'overdue') {
                $bill->update(['status' => 'overdue']);
                // Notify the resident about the overdue bill
                $bill->resident->notify(new \App\Notifications\BillOverdueNotification($bill));
            }
            return;
        }

        // If partially paid, mark as partially_paid
        if ($bill->paid_amount > 0 && !$bill->is_fully_paid) {
            $bill->update(['status' => 'partially_paid']);
            return;
        }

        // Default status is pending if nothing else applies
        if ($bill->status === 'paid' || $bill->status === 'overdue' || $bill->status === 'partially_paid') {
            // Don't change these statuses
            return;
        }

        $bill->update(['status' => 'pending']);
    }

    /**
     * Generate recurring bills based on existing bill templates
     * 
     * @return int Count of bills created
     */
    public function generateRecurringBills(): int
    {
        $count = 0;

        // Find all bills with recurrence and next_billing_date
        $recurringBills = Bill::where('recurrence', '!=', null)
            ->where('recurrence', '!=', 'none')
            ->where('next_billing_date', '<=', now())
            ->get();

        foreach ($recurringBills as $templateBill) {
            try {
                DB::transaction(function () use ($templateBill, &$count) {
                    // Create a new bill based on the template
                    $newBill = $templateBill->replicate([
                        'status',
                        'due_date',
                        'next_billing_date',
                        'created_at',
                        'updated_at'
                    ]);

                    // Set the new due date based on recurrence pattern
                    $dueDate = $this->calculateNextDueDate($templateBill->recurrence, $templateBill->due_date);
                    $newBill->due_date = $dueDate;

                    // Set the next billing date for the new bill
                    $newBill->next_billing_date = $this->calculateNextDueDate($templateBill->recurrence, $dueDate);

                    // Initialize with pending status
                    $newBill->status = 'pending';

                    // Save the new bill
                    $newBill->save();

                    // Update the template bill's next_billing_date
                    $templateBill->next_billing_date = $dueDate;
                    $templateBill->save();

                    // Notify the resident about the new recurring bill
                    $newBill->resident->notify(new \App\Notifications\NewBillNotification($newBill));

                    $count++;
                });
            } catch (Throwable $e) {
                // Log error but continue processing others
                logger()->error("Failed to create recurring bill: " . $e->getMessage(), [
                    'bill_id' => $templateBill->id,
                    'exception' => $e
                ]);
            }
        }

        return $count;
    }

    /**
     * Calculate next due date based on recurrence pattern
     *
     * @param string $recurrence
     * @param Carbon $currentDate
     * @return Carbon
     */
    private function calculateNextDueDate(string $recurrence, Carbon $currentDate): Carbon
    {
        $date = $currentDate->copy();

        switch ($recurrence) {
            case 'daily':
                return $date->addDay();
            case 'weekly':
                return $date->addWeek();
            case 'biweekly':
                return $date->addWeeks(2);
            case 'monthly':
                return $date->addMonth();
            case 'quarterly':
                return $date->addMonths(3);
            case 'biannually':
                return $date->addMonths(6);
            case 'annually':
                return $date->addYear();
            default:
                return $date->addMonth(); // Default to monthly if recurrence is unknown
        }
    }

    /**
     * Generate bills for a specific property based on type
     *
     * @param Property $property
     * @param string $billType
     * @param array $data
     * @return array Bills created
     */
    public function generatePropertyBills(Property $property, string $billType, array $data): array
    {
        $createdBills = [];

        // Get all residents associated with this property
        $residents = $property->residents;

        // Determine which residents should receive this bill type
        foreach ($residents as $resident) {
            // Get the pivot data which contains the relationship type
            $pivotData = $resident->pivot->toArray();
            $relationshipType = $pivotData['relationship_type'] ?? 'primary';

            // Determine if this resident should receive this bill type
            // based on relationship type and bill type
            $shouldReceiveBill = $this->shouldResidentReceiveBill($relationshipType, $billType);

            if ($shouldReceiveBill) {
                $billData = array_merge($data, [
                    'property_id' => $property->id,
                    'resident_id' => $resident->id,
                    'bill_type' => $billType,
                    'status' => 'pending',
                ]);

                $bill = $this->createBill($billData);
                $createdBills[] = $bill;
            }
        }

        return $createdBills;
    }

    /**
     * Determine if a resident should receive a specific bill type
     * based on their relationship to the property
     *
     * @param string $relationshipType
     * @param string $billType
     * @return bool
     */
    private function shouldResidentReceiveBill(string $relationshipType, string $billType): bool
    {
        // Define which bill types should be sent to which relationship types
        $billResponsibilities = [
            'owner' => ['maintenance', 'property_tax', 'insurance', 'mortgage'],
            'co_owner' => ['maintenance', 'property_tax', 'insurance', 'mortgage'],
            'tenant' => ['rent', 'utility', 'water', 'electricity', 'gas', 'internet'],
            'co_tenant' => ['rent', 'utility', 'water', 'electricity', 'gas', 'internet'],
            'agent' => [], // Agents typically don't receive bills directly
            'primary' => ['maintenance', 'property_tax', 'insurance', 'mortgage', 'rent', 'utility', 'water', 'electricity', 'gas', 'internet'], // Primary receives all
        ];

        // If relationship type isn't defined, default to primary
        if (!isset($billResponsibilities[$relationshipType])) {
            $relationshipType = 'primary';
        }

        return in_array($billType, $billResponsibilities[$relationshipType]);
    }
}
