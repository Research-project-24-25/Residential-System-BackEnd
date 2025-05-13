<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Bill;
use App\Models\MaintenanceRequest;
use App\Models\Property;
use App\Models\PropertyResident;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
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

        // Calculate pro-rata rental revenue from bills (accrual-based)
        for ($month = 1; $month <= $reportMonths; $month++) {
            // Get all rental bills created in this month
            $rentalBills = Bill::query()
                ->where('bill_type', 'rent')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->sum('amount');

            $monthlyRentalRevenue[$month - 1] = round((float) $rentalBills, 2);

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

        // Calculate maintenance costs - using final_cost field which represents
        // the accrued expense when work is completed
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

    public function calculateRevenue(Carbon $startDate, Carbon $endDate): float
    {
        // Sum all revenue components using accrual-based accounting
        $totalRevenue = $this->calculateTotalRevenue($startDate, $endDate);
        return $totalRevenue['total'];
    }

    public function calculateExpenditure(Carbon $startDate, Carbon $endDate): float
    {
        // Sum all expense components using accrual-based accounting
        $totalExpenses = $this->calculateTotalExpenses($startDate, $endDate);
        return $totalExpenses['total'];
    }

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

    public function calculateProfit(Carbon $startDate, Carbon $endDate): float
    {
        $revenue = $this->calculateRevenue($startDate, $endDate);
        $expenditure = $this->calculateExpenditure($startDate, $endDate);

        return $revenue - $expenditure;
    }

    private function calculateTotalRevenue(Carbon $startDate, Carbon $endDate): array
    {
        // 1. Property sales revenue
        $salesRevenue = $this->calculateSalesRevenue($startDate, $endDate);

        // 2. Rental revenue from bills
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

    private function calculateSalesRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return PropertyResident::query()
            ->whereIn('relationship_type', ['buyer', 'co_buyer'])
            ->whereNotNull('sale_price')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('sale_price');
    }

    private function calculateRentalRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return Bill::query()
            ->where('bill_type', 'rent')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    private function calculateServiceRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return Bill::query()
            ->whereIn('bill_type', ['electricity', 'gas', 'water', 'security', 'cleaning', 'internet'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    private function calculateMaintenanceRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return Bill::query()
            ->where('bill_type', 'maintenance')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    private function calculateOtherRevenue(Carbon $startDate, Carbon $endDate): float
    {
        return Bill::query()
            ->whereNotIn('bill_type', ['electricity', 'gas', 'water', 'security', 'cleaning', 'internet', 'maintenance', 'rent'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
    }

    private function calculateAdminSalaryCosts(Carbon $startDate, Carbon $endDate): float
    {
        // Get the total monthly salary costs
        $totalMonthlySalary = Admin::sum('salary');

        // Calculate how many months in the period
        $monthsInPeriod = $startDate->diffInMonths($endDate) + 1;

        return $totalMonthlySalary * $monthsInPeriod;
    }

    private function calculateMaintenanceCosts(Carbon $startDate, Carbon $endDate): float
    {
        // Use final_cost field to represent the accrued expense when work is completed
        return MaintenanceRequest::query()
            ->whereNotNull('final_cost')
            ->whereBetween('completion_date', [$startDate, $endDate])
            ->sum('final_cost');
    }

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

    private function columnExists(string $table, string $column): bool
    {
        return DB::getSchemaBuilder()->hasColumn($table, $column);
    }
}
