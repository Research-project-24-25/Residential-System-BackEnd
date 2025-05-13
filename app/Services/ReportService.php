<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Bill;
use App\Models\MaintenanceRequest;
use App\Models\Payment;
use App\Models\Property;
use App\Models\PropertyResident;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get a summary of monthly revenue for a given year.
     *
     * @param int $year The year to report on.
     * @param int $reportMonths The number of months to include in the report (default is 12).
     * @return array Monthly breakdown of different revenue categories
     */
    public function getMonthlyRevenueSummary(int $year, int $reportMonths = 12): array
    {
        $monthlySalesRevenue = array_fill(0, $reportMonths, 0.0);
        $monthlyRentalRevenue = array_fill(0, $reportMonths, 0.0);
        $monthlyServiceRevenue = array_fill(0, $reportMonths, 0.0);
        $monthlyMaintenanceRevenue = array_fill(0, $reportMonths, 0.0);
        $monthlyOtherRevenue = array_fill(0, $reportMonths, 0.0);

        // Calculate sales revenue
        $salesData = PropertyResident::query()
            ->whereIn('relationship_type', ['buyer', 'co_buyer'])
            ->whereNotNull('sale_price')
            ->whereYear('created_at', $year)
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(sale_price) as total_sales')
            )
            ->groupBy('month')
            ->get();

        foreach ($salesData as $sale) {
            if ($sale->month >= 1 && $sale->month <= $reportMonths) {
                $monthlySalesRevenue[$sale->month - 1] = (float) $sale->total_sales;
            }
        }

        // Calculate pro-rata rental revenue
        for ($month = 1; $month <= $reportMonths; $month++) {
            $currentMonthStart = Carbon::create($year, $month, 1)->startOfMonth();
            $currentMonthEnd = Carbon::create($year, $month, 1)->endOfMonth();
            $daysInCurrentMonth = $currentMonthStart->daysInMonth;

            $activeRentals = PropertyResident::query()
                ->where('relationship_type', 'renter')
                ->whereNotNull('monthly_rent')
                ->where('start_date', '<=', $currentMonthEnd)
                ->where(function ($query) use ($currentMonthStart) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $currentMonthStart);
                })
                ->get();

            $totalRentForMonth = 0;

            foreach ($activeRentals as $rental) {
                $rentalStartDate = Carbon::parse($rental->start_date);
                $rentalEndDate = $rental->end_date ? Carbon::parse($rental->end_date) : $currentMonthEnd;

                // Determine effective dates within the current month
                $effectiveStartDateInMonth = $rentalStartDate->isAfter($currentMonthStart) ? $rentalStartDate : $currentMonthStart;
                $effectiveEndDateInMonth = $rentalEndDate->isBefore($currentMonthEnd) ? $rentalEndDate : $currentMonthEnd;

                // Skip if effective period is invalid
                if ($effectiveEndDateInMonth->isBefore($effectiveStartDateInMonth)) {
                    continue;
                }

                $activeDaysInMonth = $effectiveStartDateInMonth->diffInDays($effectiveEndDateInMonth) + 1;

                if ($daysInCurrentMonth > 0) {
                    $proratedRent = ($rental->monthly_rent / $daysInCurrentMonth) * $activeDaysInMonth;
                    $totalRentForMonth += $proratedRent;
                }
            }
            $monthlyRentalRevenue[$month - 1] = round((float) $totalRentForMonth, 2);

            // Calculate service revenue (utilities, etc.)
            $serviceRevenue = Bill::query()
                ->whereIn('bill_type', ['electricity', 'gas', 'water', 'security', 'cleaning', 'internet'])
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('amount');
            $monthlyServiceRevenue[$month - 1] = round((float) $serviceRevenue, 2);

            // Calculate maintenance revenue
            $maintenanceRevenue = Bill::query()
                ->where('bill_type', 'maintenance')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('amount');
            $monthlyMaintenanceRevenue[$month - 1] = round((float) $maintenanceRevenue, 2);

            // Calculate other revenue
            $otherRevenue = Bill::query()
                ->whereNotIn('bill_type', ['electricity', 'gas', 'water', 'security', 'cleaning', 'internet', 'maintenance', 'rent'])
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('amount');
            $monthlyOtherRevenue[$month - 1] = round((float) $otherRevenue, 2);
        }

        return [
            'monthly_sales_revenue' => $monthlySalesRevenue,
            'monthly_rental_revenue' => $monthlyRentalRevenue,
            'monthly_service_revenue' => $monthlyServiceRevenue,
            'monthly_maintenance_revenue' => $monthlyMaintenanceRevenue,
            'monthly_other_revenue' => $monthlyOtherRevenue,
        ];
    }

    /**
     * Get a summary of monthly expenditures for a given year.
     *
     * @param int $year The year to report on.
     * @param int $reportMonths The number of months to include in the report (default is 12).
     * @return array Monthly breakdowns of different expenditure categories
     */
    public function getMonthlyExpenditureSummary(int $year, int $reportMonths = 12): array
    {
        $monthlySalaries = array_fill(0, $reportMonths, 0.0);
        $monthlyMaintenance = array_fill(0, $reportMonths, 0.0);
        $monthlyServices = array_fill(0, $reportMonths, 0.0);
        $monthlyAcquisition = array_fill(0, $reportMonths, 0.0);
        $monthlyOtherExpenses = array_fill(0, $reportMonths, 0.0);

        // Calculate monthly salaries
        $totalMonthlySalary = Admin::sum('salary');
        for ($month = 0; $month < $reportMonths; $month++) {
            $monthlySalaries[$month] = $totalMonthlySalary;
        }

        // Calculate maintenance costs
        $maintenanceData = MaintenanceRequest::query()
            ->whereNotNull('final_cost')
            ->whereYear('completion_date', $year)
            ->select(
                DB::raw('MONTH(completion_date) as month'),
                DB::raw('SUM(final_cost) as total_cost')
            )
            ->groupBy('month')
            ->get();

        foreach ($maintenanceData as $maintenance) {
            if ($maintenance->month >= 1 && $maintenance->month <= $reportMonths) {
                $monthlyMaintenance[$maintenance->month - 1] = (float) $maintenance->total_cost;
            }
        }

        // Calculate service provider costs
        $services = Service::whereNotNull('provider_cost')->get();

        for ($month = 1; $month <= $reportMonths; $month++) {
            $monthlyServiceCost = 0;

            foreach ($services as $service) {
                // If recurring, add the provider cost according to recurrence pattern
                if ($service->is_recurring) {
                    switch ($service->recurrence) {
                        case 'monthly':
                            $monthlyServiceCost += $service->provider_cost;
                            break;
                        case 'quarterly':
                            if (($month - 1) % 3 === 0) {
                                $monthlyServiceCost += $service->provider_cost;
                            }
                            break;
                        case 'yearly':
                            if ($month === 1) {
                                $monthlyServiceCost += $service->provider_cost;
                            }
                            break;
                    }
                }
                // For non-recurring services, add based on activation dates
                else {
                    $activeServiceCount = DB::table('property_service')
                        ->where('service_id', $service->id)
                        ->where('status', 'active')
                        ->whereYear('activated_at', $year)
                        ->whereMonth('activated_at', $month)
                        ->count();

                    $monthlyServiceCost += $service->provider_cost * $activeServiceCount;
                }
            }

            $monthlyServices[$month - 1] = round($monthlyServiceCost, 2);

            // Calculate property acquisition costs
            $acquisitionCost = Property::whereNotNull('acquisition_cost')
                ->whereYear('acquisition_date', $year)
                ->whereMonth('acquisition_date', $month)
                ->sum('acquisition_cost');

            $monthlyAcquisition[$month - 1] = round((float) $acquisitionCost, 2);
        }

        return [
            'monthly_salaries' => $monthlySalaries,
            'monthly_maintenance' => $monthlyMaintenance,
            'monthly_services' => $monthlyServices,
            'monthly_acquisition' => $monthlyAcquisition,
            'monthly_other_expenses' => $monthlyOtherExpenses,
        ];
    }

    /**
     * Get a summary of monthly profits for a given year.
     *
     * @param int $year The year to report on.
     * @param int $reportMonths The number of months to include in the report (default is 12).
     * @return array Monthly profit breakdown
     */
    public function getMonthlyProfitSummary(int $year, int $reportMonths = 12): array
    {
        // Get revenue and expenditure data
        $revenue = $this->getMonthlyRevenueSummary($year, $reportMonths);
        $expenditure = $this->getMonthlyExpenditureSummary($year, $reportMonths);

        $monthlyProfit = array_fill(0, $reportMonths, 0.0);
        $monthlyRevenue = array_fill(0, $reportMonths, 0.0);
        $monthlyExpenditure = array_fill(0, $reportMonths, 0.0);

        for ($month = 0; $month < $reportMonths; $month++) {
            // Total revenue = sales + rental + service + maintenance + other
            $totalMonthlyRevenue = $revenue['monthly_sales_revenue'][$month] +
                $revenue['monthly_rental_revenue'][$month] +
                $revenue['monthly_service_revenue'][$month] +
                $revenue['monthly_maintenance_revenue'][$month] +
                $revenue['monthly_other_revenue'][$month];

            // Total expenditure = salaries + maintenance + services + acquisition + other
            $totalMonthlyExpenditure = $expenditure['monthly_salaries'][$month] +
                $expenditure['monthly_maintenance'][$month] +
                $expenditure['monthly_services'][$month] +
                $expenditure['monthly_acquisition'][$month] +
                $expenditure['monthly_other_expenses'][$month];

            // Profit = revenue - expenditure
            $monthlyProfit[$month] = round($totalMonthlyRevenue - $totalMonthlyExpenditure, 2);
            $monthlyRevenue[$month] = round($totalMonthlyRevenue, 2);
            $monthlyExpenditure[$month] = round($totalMonthlyExpenditure, 2);
        }

        return [
            'monthly_profit' => $monthlyProfit,
            'monthly_revenue' => $monthlyRevenue,
            'monthly_expenditure' => $monthlyExpenditure,
        ];
    }

    /**
     * Calculate total revenue for a period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function calculateRevenue(Carbon $startDate, Carbon $endDate): float
    {
        // Property sales revenue
        $salesRevenue = PropertyResident::query()
            ->whereIn('relationship_type', ['buyer', 'co_buyer'])
            ->whereNotNull('sale_price')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('sale_price');

        // Rental revenue from bills
        $rentalRevenue = Bill::query()
            ->where('bill_type', 'rent')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // Service revenue
        $serviceRevenue = Bill::query()
            ->whereIn('bill_type', ['electricity', 'gas', 'water', 'security', 'cleaning', 'internet'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // Other revenue
        $otherRevenue = Bill::query()
            ->whereNotIn('bill_type', ['rent', 'electricity', 'gas', 'water', 'security', 'cleaning', 'internet'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        return (float) ($salesRevenue + $rentalRevenue + $serviceRevenue + $otherRevenue);
    }

    /**
     * Calculate total expenditure for a period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function calculateExpenditure(Carbon $startDate, Carbon $endDate): float
    {
        // Get the total monthly salary costs
        $totalMonthlySalary = Admin::sum('salary');

        // Calculate how many months in the period
        $monthsInPeriod = $startDate->diffInMonths($endDate) + 1;
        $salaryCost = $totalMonthlySalary * $monthsInPeriod;

        // Maintenance costs
        $maintenanceCost = MaintenanceRequest::query()
            ->whereNotNull('final_cost')
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->sum('final_cost');

        // Service provider costs - FIX THE QUERY TO NOT USE orWherePivot
        $serviceProviderCost = Service::query()
            ->whereNotNull('provider_cost')
            ->whereHas('properties', function ($query) use ($startDate, $endDate) {
                $query->where('property_service.status', 'active')
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('property_service.activated_at', [$startDate, $endDate])
                            ->orWhere(function ($innerQ) use ($startDate) {
                                $innerQ->where('property_service.activated_at', '<', $startDate)
                                    ->where(function ($deepQ) use ($startDate) {
                                        $deepQ->whereNull('property_service.expires_at')
                                            ->orWhere('property_service.expires_at', '>=', $startDate);
                                    });
                            });
                    });
            })
            ->sum('provider_cost');

        // Property acquisition costs
        $acquisitionCost = Property::query()
            ->whereNotNull('acquisition_cost')
            ->whereBetween('acquisition_date', [$startDate, $endDate])
            ->sum('acquisition_cost');

        return (float) ($salaryCost + $maintenanceCost + $serviceProviderCost + $acquisitionCost);
    }

    /**
     * Get revenue and expenditure breakdown for a specific period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getRevenueExpenseBreakdown(Carbon $startDate, Carbon $endDate): array
    {
        // Get all revenue and expense components
        $revenue = $this->calculateTotalRevenue($startDate, $endDate);
        $expenses = $this->calculateTotalExpenses($startDate, $endDate);
        $profit = $revenue['total'] - $expenses['total'];

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'revenue' => $revenue,
            'expenses' => $expenses,
            'profit' => $profit,
            'profit_margin' => $revenue['total'] > 0 ? round(($profit / $revenue['total']) * 100, 2) : 0,
        ];
    }

    /**
     * Calculate profit for a specific period.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    public function calculateProfit(Carbon $startDate, Carbon $endDate): float
    {
        $revenue = $this->calculateRevenue($startDate, $endDate);
        $expenditure = $this->calculateExpenditure($startDate, $endDate);

        return $revenue - $expenditure;
    }

    /**
     * Calculate total revenue with breakdown by type.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateTotalRevenue(Carbon $startDate, Carbon $endDate): array
    {
        // 1. Property sales revenue
        $salesRevenue = $this->calculateSalesRevenue($startDate, $endDate);

        // 2. Rental revenue
        $rentalRevenue = $this->calculateRentalRevenue($startDate, $endDate);

        // 3. Service charges revenue (bills for utilities, etc.)
        $serviceRevenue = $this->calculateServiceRevenue($startDate, $endDate);

        // 4. Maintenance revenue
        $maintenanceRevenue = $this->calculateMaintenanceRevenue($startDate, $endDate);

        // 5. Other bill payments
        $otherRevenue = $this->calculateOtherRevenue($startDate, $endDate);

        // Calculate total
        $total = $salesRevenue + $rentalRevenue + $serviceRevenue + $maintenanceRevenue + $otherRevenue;

        return [
            'total' => $total,
            'breakdown' => [
                'property_sales' => $salesRevenue,
                'property_rentals' => $rentalRevenue,
                'services' => $serviceRevenue,
                'maintenance' => $maintenanceRevenue,
                'other' => $otherRevenue,
            ]
        ];
    }

    /**
     * Calculate total expenses with breakdown by type.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    private function calculateTotalExpenses(Carbon $startDate, Carbon $endDate): array
    {
        // 1. Admin salary costs
        $adminSalaries = $this->calculateAdminSalaryCosts($startDate, $endDate);

        // 2. Maintenance costs
        $maintenanceCosts = $this->calculateMaintenanceCosts($startDate, $endDate);

        // 3. Service provider costs
        $serviceProviderCosts = $this->calculateServiceProviderCosts($startDate, $endDate);

        // 4. Property acquisition costs
        $propertyAcquisitionCosts = $this->calculatePropertyAcquisitionCosts($startDate, $endDate);

        // Calculate total
        $total = $adminSalaries + $maintenanceCosts + $serviceProviderCosts + $propertyAcquisitionCosts;

        return [
            'total' => $total,
            'breakdown' => [
                'admin_salaries' => $adminSalaries,
                'maintenance_costs' => $maintenanceCosts,
                'service_provider_costs' => $serviceProviderCosts,
                'property_acquisition' => $propertyAcquisitionCosts,
            ]
        ];
    }

    /**
     * Calculate property sales revenue.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateSalesRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return PropertyResident::query()
            ->whereIn('relationship_type', ['buyer', 'co_buyer'])
            ->whereNotNull('sale_price')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('sale_price');
    }

    /**
     * Calculate rental revenue.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateRentalRevenue(Carbon $startDate, Carbon $endDate): float
    {
        $totalRentalRevenue = 0;

        // Get all active rental agreements in the period
        $rentalAgreements = PropertyResident::query()
            ->where('relationship_type', 'renter')
            ->whereNotNull('monthly_rent')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    // Started before or during period
                    $q->where('start_date', '<=', $endDate)
                        // And either has no end date or ends after start of period
                        ->where(function ($innerQ) use ($startDate) {
                            $innerQ->whereNull('end_date')
                                ->orWhere('end_date', '>=', $startDate);
                        });
                });
            })
            ->get();

        // Calculate pro-rated rental revenue for each agreement
        foreach ($rentalAgreements as $rental) {
            $rentalStartDate = Carbon::parse($rental->start_date);
            $rentalEndDate = $rental->end_date ? Carbon::parse($rental->end_date) : $endDate;

            // Determine effective start and end dates within the reporting period
            $effectiveStartDate = $rentalStartDate->isAfter($startDate) ? $rentalStartDate : $startDate;
            $effectiveEndDate = $rentalEndDate->isBefore($endDate) ? $rentalEndDate : $endDate;

            // Skip if effective period is invalid
            if ($effectiveEndDate->isBefore($effectiveStartDate)) {
                continue;
            }

            // Calculate active days in period
            $activeDaysInPeriod = $effectiveStartDate->diffInDays($effectiveEndDate) + 1;

            // Calculate pro-rated revenue
            $proRatedRevenue = $rental->monthly_rent * ($activeDaysInPeriod / 30); // Assuming 30 days per month on average
            $totalRentalRevenue += $proRatedRevenue;
        }

        return round($totalRentalRevenue, 2);
    }

    /**
     * Calculate service-related revenue.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateServiceRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return Bill::query()
            ->whereIn('bill_type', ['electricity', 'gas', 'water', 'security', 'cleaning', 'internet'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Calculate maintenance-related revenue.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateMaintenanceRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return Bill::query()
            ->where('bill_type', 'maintenance')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Calculate other revenue.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateOtherRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return Bill::query()
            ->whereNotIn('bill_type', ['electricity', 'gas', 'water', 'security', 'cleaning', 'internet', 'maintenance'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    /**
     * Calculate admin salary costs.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateAdminSalaryCosts(Carbon $startDate, Carbon $endDate): float
    {
        // Get the total monthly salary costs
        $totalMonthlySalary = Admin::sum('salary');

        // Calculate how many months in the period
        $monthsInPeriod = $startDate->diffInMonths($endDate) + 1;

        return $totalMonthlySalary * $monthsInPeriod;
    }

    /**
     * Calculate maintenance costs.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateMaintenanceCosts(Carbon $startDate, Carbon $endDate): float
    {
        // When the actual_cost column is available, use it instead of final_cost
        $actualCostExists = $this->columnExists('maintenance_requests', 'actual_cost');

        $query = MaintenanceRequest::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($actualCostExists) {
            $query->whereNotNull('actual_cost');
            return $query->sum('actual_cost');
        } else {
            $query->whereNotNull('final_cost');
            return $query->sum('final_cost');
        }
    }

    /**
     * Calculate service provider costs.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculateServiceProviderCosts(Carbon $startDate, Carbon $endDate): float
    {
        // Check if provider_cost column exists in services table
        $providerCostExists = $this->columnExists('services', 'provider_cost');

        // If the column doesn't exist, return 0
        if (!$providerCostExists) {
            return 0;
        }

        // Get all active services in the period - Fixed the orWherePivot issue
        $activeServices = DB::table('property_service')
            ->join('services', 'services.id', '=', 'property_service.service_id')
            ->where('property_service.status', 'active')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('property_service.activated_at', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate) {
                        $q->where('property_service.activated_at', '<', $startDate)
                            ->where(function ($innerQ) use ($startDate) {
                                $innerQ->whereNull('property_service.expires_at')
                                    ->orWhere('property_service.expires_at', '>=', $startDate);
                            });
                    });
            })
            ->select('services.provider_cost', 'services.recurrence', 'property_service.*')
            ->get();

        $totalProviderCost = 0;

        foreach ($activeServices as $service) {
            // Skip if no provider cost
            if (empty($service->provider_cost)) {
                continue;
            }

            $serviceStartDate = Carbon::parse($service->activated_at);
            $serviceEndDate = $service->expires_at ? Carbon::parse($service->expires_at) : $endDate;

            // Determine effective start and end dates within the reporting period
            $effectiveStartDate = $serviceStartDate->isAfter($startDate) ? $serviceStartDate : $startDate;
            $effectiveEndDate = $serviceEndDate->isBefore($endDate) ? $serviceEndDate : $endDate;

            // Skip if effective period is invalid
            if ($effectiveEndDate->isBefore($effectiveStartDate)) {
                continue;
            }

            // Calculate service cost based on recurrence pattern
            $monthsInPeriod = $effectiveStartDate->diffInMonths($effectiveEndDate) + 1;

            switch ($service->recurrence) {
                case 'monthly':
                    $totalProviderCost += $service->provider_cost * $monthsInPeriod;
                    break;
                case 'quarterly':
                    $totalProviderCost += $service->provider_cost * ceil($monthsInPeriod / 3);
                    break;
                case 'yearly':
                    $totalProviderCost += $service->provider_cost * ceil($monthsInPeriod / 12);
                    break;
                default:
                    // For one-time or unknown recurrence, just add the provider cost once
                    $totalProviderCost += $service->provider_cost;
                    break;
            }
        }

        return $totalProviderCost;
    }

    /**
     * Calculate property acquisition costs.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    private function calculatePropertyAcquisitionCosts(Carbon $startDate, Carbon $endDate): float
    {
        // Check if acquisition_cost and acquisition_date columns exist in properties table
        $acquisitionColumnsExist = $this->columnExists('properties', 'acquisition_cost') &&
            $this->columnExists('properties', 'acquisition_date');

        // If the columns don't exist, return 0
        if (!$acquisitionColumnsExist) {
            return 0;
        }

        return Property::query()
            ->whereNotNull('acquisition_cost')
            ->whereNotNull('acquisition_date')
            ->whereBetween('acquisition_date', [$startDate, $endDate])
            ->sum('acquisition_cost');
    }

    /**
     * Helper method to check if a column exists in a table.
     *
     * @param string $table
     * @param string $column
     * @return bool
     */
    private function columnExists(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
