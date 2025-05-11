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
use App\Models\ServiceRequest;
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
                'active' => $this->countModel(Service::class, ['is_active' => true]),
                'requests' => [
                    'total' => $this->countModel(ServiceRequest::class),
                    'pending' => $this->countModel(ServiceRequest::class, ['status' => 'pending']),
                    'in_progress' => $this->countModel(ServiceRequest::class, ['status' => ['approved', 'scheduled', 'in_progress']]),
                    'completed' => $this->countModel(ServiceRequest::class, ['status' => 'completed']),
                ],
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
            'recent_service_requests' => $this->fetchRecent(ServiceRequest::class, ['service', 'property', 'resident'], $limit)
                ->map(function ($serviceRequest) {
                    return [
                        'id' => $serviceRequest->id,
                        'status' => $serviceRequest->status,
                        'requested_date' => $serviceRequest->requested_date,
                        'service' => $serviceRequest->service ? [
                            'id' => $serviceRequest->service->id,
                            'name' => $serviceRequest->service->name,
                            'type' => $serviceRequest->service->type,
                        ] : null,
                        'property' => $serviceRequest->property ? [
                            'id' => $serviceRequest->property->id,
                            'label' => $serviceRequest->property->label,
                        ] : null,
                        'resident' => $serviceRequest->resident ? [
                            'id' => $serviceRequest->resident->id,
                            'name' => $serviceRequest->resident->name,
                        ] : null,
                        'created_at' => $serviceRequest->created_at,
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

    /**
     * Get service statistics
     *
     * @return array
     */
    public function getServiceStats(): array
    {
        // Get counts by service type
        $byType = $this->getGroupedCount(Service::class, 'type');

        // Get active vs inactive
        $activeStatus = [
            'active' => $this->countModel(Service::class, ['is_active' => true]),
            'inactive' => $this->countModel(Service::class, ['is_active' => false]),
        ];

        // Get service request statistics
        $requestsByStatus = $this->getGroupedCount(ServiceRequest::class, 'status');

        // Get service requests created this month
        $requestsThisMonth = ServiceRequest::query()->whereMonth('created_at', now()->month)
            ->count();

        // Get most requested services
        $mostRequested = Service::join('service_requests', 'services.id', '=', 'service_requests.service_id')
            ->select('services.id', 'services.name', DB::raw('count(service_requests.id) as request_count'))
            ->groupBy('services.id', 'services.name')
            ->orderBy('request_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'request_count' => $item->request_count,
                ];
            })
            ->toArray();

        return [
            'total_services' => $this->countModel(Service::class),
            'total_service_requests' => $this->countModel(ServiceRequest::class),
            'services_by_type' => $byType,
            'service_status' => $activeStatus,
            'requests_by_status' => $requestsByStatus,
            'requests_this_month' => $requestsThisMonth,
            'most_requested_services' => $mostRequested,
        ];
    }

    /**
     * Calculate total revenue
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateTotalRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return $this->sumPaymentsInDateRange($startDate, $endDate);
    }

    /**
     * Calculate current period revenue
     *
     * @param string $period
     * @return float
     */
    private function calculateCurrentPeriodRevenue(string $period): float
    {
        $dateRange = $this->getCurrentPeriodDates($period);
        return $this->sumPaymentsInDateRange($dateRange['start'], $dateRange['end']);
    }

    /**
     * Calculate total costs (using bills for simplicity)
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateTotalCosts(Carbon $startDate, Carbon $endDate): float
    {
        // For simplicity, we're calculating costs as the total of expenses-type bills
        // In a real system, this would likely be more complex
        return $this->sumBillsInDateRange($startDate, $endDate, ['maintenance', 'security', 'cleaning']);
    }

    /**
     * Calculate current period costs
     *
     * @param string $period
     * @return float
     */
    private function calculateCurrentPeriodCosts(string $period): float
    {
        $dateRange = $this->getCurrentPeriodDates($period);
        return $this->sumBillsInDateRange($dateRange['start'], $dateRange['end'], ['maintenance', 'security', 'cleaning']);
    }

    /**
     * Sums payments within a given date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function sumPaymentsInDateRange(Carbon $startDate, Carbon $endDate): float
    {
        return Payment::where('status', 'paid') // Changed from 'completed'
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Sums bills of specified types within a given date range.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $billTypes
     * @return float
     */
    private function sumBillsInDateRange(Carbon $startDate, Carbon $endDate, array $billTypes): float
    {
        return Bill::whereIn('bill_type', $billTypes)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Calculate profit
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateProfit(Carbon $startDate, Carbon $endDate): float
    {
        $revenue = $this->calculateTotalRevenue($startDate, $endDate);
        $costs = $this->calculateTotalCosts($startDate, $endDate);

        return $revenue - $costs;
    }

    // getRevenueByPaymentMethod method removed

    /**
     * Get revenue by bill type
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
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

    /**
     * Calculate occupancy rate (percentage of properties that are rented or sold)
     *
     * @return float
     */
    private function calculateOccupancyRate(): float
    {
        $totalProperties = $this->countModel(Property::class);

        if ($totalProperties === 0) {
            return 0;
        }

        $occupiedProperties = $this->countModel(Property::class, ['status' => ['rented', 'sold']]);

        return round(($occupiedProperties / $totalProperties) * 100, 2);
    }

    /**
     * Get residents by age group
     *
     * @return array
     */
    private function getResidentsByAgeGroup(): array
    {
        return [
            'under_30' => Resident::where('age', '<', 30)->count(),
            '30_to_45' => Resident::whereBetween('age', [30, 45])->count(),
            '46_to_60' => Resident::whereBetween('age', [46, 60])->count(),
            'over_60' => Resident::where('age', '>', 60)->count(),
        ];
    }

    /**
     * Get date range based on period
     *
     * @param string $period
     * @return array
     */
    private function getDateRange(string $period): array
    {
        return $this->calculateDateRange($period, false);
    }

    /**
     * Get current period date range
     *
     * @param string $period
     * @return array
     */
    private function getCurrentPeriodDates(string $period): array
    {
        return $this->calculateDateRange($period, true);
    }

    /**
     * Calculate date range based on period and whether it's for the current, ongoing period.
     *
     * @param string $period (month, quarter, year, all)
     * @param bool $forCurrentOngoingPeriod If true, end date is Carbon::now().
     * @return array ['start' => Carbon, 'end' => Carbon]
     */
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

    /**
     * Format period label for display
     *
     * @param string $period
     * @return string
     */
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

    /**
     * Get total user count across all user types
     *
     * @return int
     */
    private function getTotalUserCount(): int
    {
        return $this->countModel(Admin::class) + $this->countModel(Resident::class) + $this->countModel(User::class);
    }

    /**
     * Count records for a given model with optional conditions.
     *
     * @param string $modelClass The fully qualified class name of the model.
     * @param array $conditions An associative array of conditions, e.g., ['status' => 'active', 'type' => ['type1', 'type2']].
     * @return int
     */
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

    /**
     * Fetches recent items from a model.
     *
     * @param string $modelClass
     * @param array $relations
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function fetchRecent(string $modelClass, array $relations = [], int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        return $modelClass::with($relations)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get counts grouped by a specific column.
     *
     * @param string $modelClass
     * @param string $groupByColumn
     * @param string $countColumn
     * @param string $alias
     * @return array
     */
    private function getGroupedCount(string $modelClass, string $groupByColumn, string $countColumn = '*', string $alias = 'total'): array
    {
        return $modelClass::select($groupByColumn, DB::raw("count($countColumn) as $alias"))
            ->groupBy($groupByColumn)
            ->pluck($alias, $groupByColumn)
            ->toArray();
    }
}
