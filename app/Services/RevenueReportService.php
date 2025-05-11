<?php

namespace App\Services;

use App\Models\PropertyResident;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueReportService
{
    /**
     * Get a summary of monthly sales and rental revenue for a given year.
     *
     * @param int $year The year to report on.
     * @param int $reportMonths The number of months to include in the report (default is 12).
     * @return array{monthly_sales_revenue: array<float>, monthly_rental_revenue: array<float>}
     */
    public function getMonthlyRevenueSummary(int $year, int $reportMonths = 12): array
    {
        $monthlySalesRevenue = array_fill(0, $reportMonths, 0.0);
        $monthlyRentalRevenue = array_fill(0, $reportMonths, 0.0);

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
        // Iterate through each month of the reporting period
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

                // Determine effective start and end dates for the rental within the current month
                $effectiveStartDateInMonth = $rentalStartDate->isAfter($currentMonthStart) ? $rentalStartDate : $currentMonthStart;
                $effectiveEndDateInMonth = $rentalEndDate->isBefore($currentMonthEnd) ? $rentalEndDate : $currentMonthEnd;
                
                // Ensure effective end date is not before effective start date
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
        }

        return [
            'monthly_sales_revenue' => $monthlySalesRevenue,
            'monthly_rental_revenue' => $monthlyRentalRevenue,
        ];
    }
}