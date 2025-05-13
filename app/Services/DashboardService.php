<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Bill;
use App\Models\MeetingRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Resident;
use App\Models\User;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get dashboard overview
     *
     * @param string $period
     * @return array
     */
    public function getOverview(string $period = 'month'): array
    {
        $dateRange = $this->getDateRange($period);

        return [
            'counts' => [
                'properties' => $this->countModel(Property::class),
                'users' => [
                    'total' => $this->getTotalUserCount(),
                    'admins' => $this->countModel(Admin::class),
                    'residents' => $this->countModel(Resident::class),
                    'regular_users' => $this->countModel(User::class),
                ],
                'bills' => $this->countModel(Bill::class),
                'payments' => $this->countModel(Payment::class),
            ],
            'revenue' => [
                'total' => $this->calculateTotalRevenue($dateRange['start'], $dateRange['end']),
                'current_period' => $this->calculateCurrentPeriodRevenue($period),
                'period' => $period,
            ],
            'costs' => [
                'total' => $this->calculateTotalCosts($dateRange['start'], $dateRange['end']),
                'current_period' => $this->calculateCurrentPeriodCosts($period),
                'period' => $period,
            ],
            'profit' => $this->calculateProfit($dateRange['start'], $dateRange['end']),
            'property_stats' => [
                'available' => $this->countModel(Property::class, ['status' => 'available_now']),
                'rented' => $this->countModel(Property::class, ['status' => 'rented']),
                'sold' => $this->countModel(Property::class, ['status' => 'sold']),
                'under_construction' => $this->countModel(Property::class, ['status' => 'under_construction']),
            ],
            'services' => [
                'total' => $this->countModel(Service::class),
            ],
        ];
    }

    /**
     * Get recent activity for dashboard
     *
     * @param int $limit
     * @return array
     */
    public function getRecentActivity(int $limit = 5): array
    {
        return [
            'recent_payments' => $this->fetchRecent(Payment::class, ['resident', 'bill'], $limit)
                ->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'currency' => $payment->currency,
                        'status' => $payment->status,
                        'resident' => $payment->resident ? [
                            'id' => $payment->resident->id,
                            'name' => $payment->resident->name,
                        ] : null,
                        'bill_type' => $payment->bill ? $payment->bill->bill_type : null,
                        'created_at' => $payment->created_at,
                    ];
                }),
            'recent_residents' => $this->fetchRecent(Resident::class, [], $limit)
                ->map(function ($resident) {
                    return [
                        'id' => $resident->id,
                        'name' => $resident->name,
                        'email' => $resident->email,
                        'created_at' => $resident->created_at,
                    ];
                }),
            'recent_meeting_requests' => $this->fetchRecent(MeetingRequest::class, ['property', 'user'], $limit)
                ->map(function ($meeting) {
                    return [
                        'id' => $meeting->id,
                        'status' => $meeting->status,
                        'requested_date' => $meeting->requested_date,
                        'property' => $meeting->property ? [
                            'id' => $meeting->property->id,
                            'label' => $meeting->property->label,
                        ] : null,
                        'user' => $meeting->user ? [
                            'id' => $meeting->user->id,
                            'name' => $meeting->user->name,
                        ] : null,
                        'created_at' => $meeting->created_at,
                    ];
                }),
        ];
    }

    /**
     * Get revenue statistics
     *
     * @param string $period
     * @return array
     */
    public function getRevenueStats(string $period = 'month'): array
    {
        $dateRange = $this->getDateRange($period);

        // Get total revenue
        $totalRevenue = $this->calculateTotalRevenue($dateRange['start'], $dateRange['end']);

        // Get revenue by payment method - REMOVED

        // Get revenue by bill type
        $revenueByBillType = $this->getRevenueByBillType($dateRange['start'], $dateRange['end']);

        // Format time periods
        $formattedPeriod = $this->formatPeriodLabel($period);

        return [
            'total' => $totalRevenue,
            // 'by_method' => $revenueByMethod, // This has been removed
            'by_bill_type' => $revenueByBillType,
            'period' => $formattedPeriod,
        ];
    }

    /**
     * Get property statistics
     *
     * @return array
     */
    public function getPropertyStats(): array
    {
        // Get counts by property type
        $byType = $this->getGroupedCount(Property::class, 'type');

        // Get counts by property status
        $byStatus = $this->getGroupedCount(Property::class, 'status');

        // Get avg price by property type
        $avgPriceByType = Property::select('type', DB::raw('avg(price) as avg_price'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->type => round($item->avg_price, 2)];
            })
            ->toArray();

        return [
            'total' => $this->countModel(Property::class),
            'by_type' => $byType,
            'by_status' => $byStatus,
            'avg_price_by_type' => $avgPriceByType,
            'occupancy_rate' => $this->calculateOccupancyRate(),
        ];
    }

    /**
     * Get user statistics
     *
     * @return array
     */
    public function getUserStats(): array
    {
        return [
            'total' => $this->getTotalUserCount(),
            'counts' => [
                'admins' => $this->countModel(Admin::class),
                'residents' => $this->countModel(Resident::class),
                'regular_users' => $this->countModel(User::class),
            ],
            'admins_by_role' => [
                'admin' => $this->countModel(Admin::class, ['role' => 'admin']),
                'super_admin' => $this->countModel(Admin::class, ['role' => 'super_admin']),
            ],
            'residents_by_gender' => [
                'male' => $this->countModel(Resident::class, ['gender' => 'male']),
                'female' => $this->countModel(Resident::class, ['gender' => 'female']),
            ],
            'residents_by_age_group' => $this->getResidentsByAgeGroup(),
            'new_users_this_month' => [
                'admins' => Admin::query()->whereMonth('created_at', now()->month)->count(),
                'residents' => Resident::query()->whereMonth('created_at', now()->month)->count(),
                'regular_users' => User::query()->whereMonth('created_at', now()->month)->count(),
            ],
        ];
    }

    public function getServiceStats(): array
    {
        // Get counts by service type
        $byType = $this->getGroupedCount(Service::class, 'type');

        return [
            'total_services' => $this->countModel(Service::class),
            'services_by_type' => $byType,
        ];
    }

    private function calculateTotalRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return $this->sumPaymentsInDateRange($startDate, $endDate);
    }

    private function calculateCurrentPeriodRevenue(string $period): float
    {
        $dateRange = $this->getCurrentPeriodDates($period);
        return $this->sumPaymentsInDateRange($dateRange['start'], $dateRange['end']);
    }

    private function calculateTotalCosts(Carbon $startDate, Carbon $endDate): float
    {
        // For simplicity, we're calculating costs as the total of expenses-type bills
        // In a real system, this would likely be more complex
        return $this->sumBillsInDateRange($startDate, $endDate, ['maintenance', 'security', 'cleaning']);
    }

    private function calculateCurrentPeriodCosts(string $period): float
    {
        $dateRange = $this->getCurrentPeriodDates($period);
        return $this->sumBillsInDateRange($dateRange['start'], $dateRange['end'], ['maintenance', 'security', 'cleaning']);
    }

    private function sumPaymentsInDateRange(Carbon $startDate, Carbon $endDate): float
    {
        return Payment::where('status', 'paid') // Changed from 'completed'
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    private function sumBillsInDateRange(Carbon $startDate, Carbon $endDate, array $billTypes): float
    {
        return Bill::whereIn('bill_type', $billTypes)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    private function calculateProfit(Carbon $startDate, Carbon $endDate): float
    {
        $revenue = $this->calculateTotalRevenue($startDate, $endDate);
        $costs = $this->calculateTotalCosts($startDate, $endDate);

        return $revenue - $costs;
    }
    private function getRevenueByBillType(Carbon $startDate, Carbon $endDate): array
    {
        return Payment::join('bills', 'payments.bill_id', '=', 'bills.id')
            ->where('payments.status', 'paid') // Changed from 'completed'
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->select('bills.bill_type', DB::raw('sum(payments.amount) as total'))
            ->groupBy('bills.bill_type')
            ->get()
            ->pluck('total', 'bill_type')
            ->toArray();
    }

    private function calculateOccupancyRate(): float
    {
        $totalProperties = $this->countModel(Property::class);

        if ($totalProperties === 0) {
            return 0;
        }

        $occupiedProperties = $this->countModel(Property::class, ['status' => ['rented', 'sold']]);

        return round(($occupiedProperties / $totalProperties) * 100, 2);
    }

    private function getResidentsByAgeGroup(): array
    {
        return [
            'under_30' => Resident::where('age', '<', 30)->count(),
            '30_to_45' => Resident::whereBetween('age', [30, 45])->count(),
            '46_to_60' => Resident::whereBetween('age', [46, 60])->count(),
            'over_60' => Resident::where('age', '>', 60)->count(),
        ];
    }

    private function getDateRange(string $period): array
    {
        return $this->calculateDateRange($period, false);
    }

    private function getCurrentPeriodDates(string $period): array
    {
        return $this->calculateDateRange($period, true);
    }

    private function calculateDateRange(string $period, bool $forCurrentOngoingPeriod): array
    {
        $now = Carbon::now();
        $start = null;
        $end = null;

        // Handle default for getCurrentPeriodDates if period is not recognized before the switch
        if ($forCurrentOngoingPeriod && !in_array($period, ['month', 'quarter', 'year', 'all'])) {
            $period = 'month'; // Default to month for current period if not specified
        }

        switch ($period) {
            case 'month':
                $start = $now->copy()->startOfMonth();
                // For getDateRange (forCurrentOngoingPeriod = false), end will be adjusted later if not 'all'
                // For getCurrentPeriodDates (forCurrentOngoingPeriod = true), end is $now
                $end = $forCurrentOngoingPeriod ? $now : $now->copy()->endOfMonth();
                break;
            case 'quarter':
                $start = $now->copy()->startOfQuarter();
                $end = $forCurrentOngoingPeriod ? $now : $now->copy()->endOfQuarter();
                break;
            case 'year':
                $start = $now->copy()->startOfYear();
                $end = $forCurrentOngoingPeriod ? $now : $now->copy()->endOfYear();
                break;
            case 'all':
                $start = Carbon::createFromDate(2000, 1, 1); // Very old date to include everything
                $end = $now; // For 'all', end is always now.
                break;
            default:
                // This case handles unknown periods for getDateRange (when forCurrentOngoingPeriod is false)
                // For getCurrentPeriodDates, unknown periods are defaulted to 'month' above.
                $start = Carbon::createFromDate(2000, 1, 1);
                $end = $now;
                break;
        }

        // Original getDateRange (when $forCurrentOngoingPeriod is false) always used Carbon::now() as the end date for specific periods.
        if (!$forCurrentOngoingPeriod && $period !== 'all') {
            $end = Carbon::now();
        }

        return ['start' => $start, 'end' => $end];
    }

    private function formatPeriodLabel(string $period): string
    {
        $now = Carbon::now();

        switch ($period) {
            case 'month':
                return $now->format('F Y');
            case 'quarter':
                return 'Q' . $now->quarter . ' ' . $now->year;
            case 'year':
                return $now->year;
            default:
                return 'All Time';
        }
    }

    private function getTotalUserCount(): int
    {
        return $this->countModel(Admin::class) + $this->countModel(Resident::class) + $this->countModel(User::class);
    }

    private function countModel(string $modelClass, array $conditions = []): int
    {
        $query = $modelClass::query();

        foreach ($conditions as $column => $value) {
            if (is_array($value)) {
                $query->whereIn($column, $value);
            } else {
                $query->where($column, $value);
            }
        }

        return $query->count();
    }

    private function fetchRecent(string $modelClass, array $relations = [], int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $modelClass::with($relations)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    private function getGroupedCount(string $modelClass, string $groupByColumn, string $countColumn = '*', string $alias = 'total'): array
    {
        return $modelClass::select($groupByColumn, DB::raw("count($countColumn) as $alias"))
            ->groupBy($groupByColumn)
            ->pluck($alias, $groupByColumn)
            ->toArray();
    }
}
