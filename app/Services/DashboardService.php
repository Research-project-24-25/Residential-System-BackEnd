<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Bill;
use App\Models\MeetingRequest;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Property;
use App\Models\Resident;
use App\Models\User;
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
                'properties' => Property::count(),
                'users' => [
                    'total' => $this->getTotalUserCount(),
                    'admins' => Admin::count(),
                    'residents' => Resident::count(),
                    'regular_users' => User::count(),
                ],
                'bills' => Bill::count(),
                'payments' => Payment::count(),
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
                'available' => Property::where('status', 'available_now')->count(),
                'rented' => Property::where('status', 'rented')->count(),
                'sold' => Property::where('status', 'sold')->count(),
                'under_construction' => Property::where('status', 'under_construction')->count(),
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
            'recent_payments' => Payment::with(['resident', 'bill'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
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
            'recent_residents' => Resident::orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($resident) {
                    return [
                        'id' => $resident->id,
                        'name' => $resident->name,
                        'email' => $resident->email,
                        'created_at' => $resident->created_at,
                    ];
                }),
            'recent_meeting_requests' => MeetingRequest::with(['property', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
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

        // Get revenue by payment method
        $revenueByMethod = $this->getRevenueByPaymentMethod($dateRange['start'], $dateRange['end']);

        // Get revenue by bill type
        $revenueByBillType = $this->getRevenueByBillType($dateRange['start'], $dateRange['end']);

        // Format time periods
        $formattedPeriod = $this->formatPeriodLabel($period);

        return [
            'total' => $totalRevenue,
            'by_method' => $revenueByMethod,
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
        $byType = Property::select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->get()
            ->pluck('total', 'type')
            ->toArray();

        // Get counts by property status
        $byStatus = Property::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        // Get avg price by property type
        $avgPriceByType = Property::select('type', DB::raw('avg(price) as avg_price'))
            ->groupBy('type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->type => round($item->avg_price, 2)];
            })
            ->toArray();

        return [
            'total' => Property::count(),
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
                'admins' => Admin::count(),
                'residents' => Resident::count(),
                'regular_users' => User::count(),
            ],
            'admins_by_role' => [
                'admin' => Admin::where('role', 'admin')->count(),
                'super_admin' => Admin::where('role', 'super_admin')->count(),
            ],
            'residents_by_gender' => [
                'male' => Resident::where('gender', 'male')->count(),
                'female' => Resident::where('gender', 'female')->count(),
            ],
            'residents_by_age_group' => $this->getResidentsByAgeGroup(),
            'new_users_this_month' => [
                'admins' => Admin::whereMonth('created_at', now()->month)->count(),
                'residents' => Resident::whereMonth('created_at', now()->month)->count(),
                'regular_users' => User::whereMonth('created_at', now()->month)->count(),
            ],
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
        return Payment::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
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

        return Payment::where('status', 'completed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');
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
        return Bill::whereIn('bill_type', ['maintenance', 'security', 'cleaning'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
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

        return Bill::whereIn('bill_type', ['maintenance', 'security', 'cleaning'])
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
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

    /**
     * Get revenue by payment method
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function getRevenueByPaymentMethod(Carbon $startDate, Carbon $endDate): array
    {
        $paymentMethodRevenue = Payment::join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
            ->where('payments.status', 'completed')
            ->whereBetween('payments.created_at', [$startDate, $endDate])
            ->select('payment_methods.type', DB::raw('sum(payments.amount) as total'))
            ->groupBy('payment_methods.type')
            ->get()
            ->pluck('total', 'type')
            ->toArray();

        // Add cash payments (where payment_method_id might be null)
        $cashPayments = Payment::whereNull('payment_method_id')
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        if ($cashPayments > 0) {
            $paymentMethodRevenue['cash'] = ($paymentMethodRevenue['cash'] ?? 0) + $cashPayments;
        }

        return $paymentMethodRevenue;
    }

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
            ->where('payments.status', 'completed')
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
        $totalProperties = Property::count();

        if ($totalProperties === 0) {
            return 0;
        }

        $occupiedProperties = Property::whereIn('status', ['rented', 'sold'])->count();

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
        $end = Carbon::now();

        switch ($period) {
            case 'month':
                $start = Carbon::now()->startOfMonth();
                break;
            case 'quarter':
                $start = Carbon::now()->startOfQuarter();
                break;
            case 'year':
                $start = Carbon::now()->startOfYear();
                break;
            case 'all':
            default:
                $start = Carbon::createFromDate(2000, 1, 1); // Very old date to include everything
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Get current period date range
     *
     * @param string $period
     * @return array
     */
    private function getCurrentPeriodDates(string $period): array
    {
        $now = Carbon::now();

        switch ($period) {
            case 'month':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now,
                ];
            case 'quarter':
                return [
                    'start' => $now->copy()->startOfQuarter(),
                    'end' => $now,
                ];
            case 'year':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now,
                ];
            default:
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now,
                ];
        }
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
        return Admin::count() + Resident::count() + User::count();
    }
}
