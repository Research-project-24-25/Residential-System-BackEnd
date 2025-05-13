<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use App\Services\RevenueReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService,
        private RevenueReportService $revenueReportService
    ) {}

    public function overview(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // month, year, all
            $dashboard = $this->dashboardService->getOverview($period);

            return $this->successResponse(
                'Dashboard data retrieved successfully',
                new DashboardResource($dashboard)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function recentActivity(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 5);
            $activity = $this->dashboardService->getRecentActivity($limit);

            return $this->successResponse(
                'Recent activity retrieved successfully',
                $activity
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function revenue(Request $request): JsonResponse
    {
        try {
            $period = $request->get('period', 'month'); // month, quarter, year
            $revenue = $this->dashboardService->getRevenueStats($period);

            return $this->successResponse(
                'Revenue statistics retrieved successfully',
                $revenue
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function properties(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getPropertyStats();

            return $this->successResponse(
                'Property statistics retrieved successfully',
                $stats
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function users(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getUserStats();

            return $this->successResponse(
                'User statistics retrieved successfully',
                $stats
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function services(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getServiceStats();

            return $this->successResponse(
                'Service statistics retrieved successfully',
                $stats
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function revenues(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'year' => 'sometimes|integer|min:1900|max:' . (Carbon::now()->year + 5),
                'months' => 'sometimes|integer|min:1|max:24'
            ]);

            $year = $request->input('year', Carbon::now()->year);
            $months = $request->input('months', 12);

            $revenueData = $this->revenueReportService->getMonthlyRevenueSummary($year, $months);

            return $this->successResponse(
                'Reveneue data retrieved successfully',
                [
                    'year' => $year,
                    'report_months_count' => $months,
                    'monthly_sales_revenue' => $revenueData['monthly_sales_revenue'],
                    'monthly_rental_revenue' => $revenueData['monthly_rental_revenue'],
                    'monthly_service_revenue' => $revenueData['monthly_service_revenue'],
                    'monthly_maintenance_revenue' => $revenueData['monthly_maintenance_revenue'],
                    'monthly_other_revenue' => $revenueData['monthly_other_revenue'],
                ]
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get expenditures data for dashboard
     */
    public function expenditures(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'year' => 'sometimes|integer|min:1900|max:' . (Carbon::now()->year + 5),
                'months' => 'sometimes|integer|min:1|max:24'
            ]);

            $year = $request->input('year', Carbon::now()->year);
            $months = $request->input('months', 12);

            $expenditureData = $this->revenueReportService->getMonthlyExpenditureSummary($year, $months);

            return $this->successResponse(
                'Expenditure data retrieved successfully',
                [
                    'year' => $year,
                    'report_months_count' => $months,
                    'monthly_salaries' => $expenditureData['monthly_salaries'],
                    'monthly_maintenance' => $expenditureData['monthly_maintenance'],
                    'monthly_services' => $expenditureData['monthly_services'],
                ]
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get profits data for dashboard
     */
    public function profits(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'year' => 'sometimes|integer|min:1900|max:' . (Carbon::now()->year + 5),
                'months' => 'sometimes|integer|min:1|max:24'
            ]);

            $year = $request->input('year', Carbon::now()->year);
            $months = $request->input('months', 12);

            $profitData = $this->revenueReportService->getMonthlyProfitSummary($year, $months);

            return $this->successResponse(
                'Profit data retrieved successfully',
                [
                    'year' => $year,
                    'report_months_count' => $months,
                    'monthly_profit' => $profitData['monthly_profit'],
                ]
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get financial summary data for dashboard
     */
    public function financialSummary(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));

            $revenue = $this->revenueReportService->calculateRevenue($startDate, $endDate);
            $expenditure = $this->revenueReportService->calculateExpenditure($startDate, $endDate);
            $profit = $revenue - $expenditure;
            $profitMargin = $revenue > 0 ? round(($profit / $revenue) * 100, 2) : 0;

            return $this->successResponse(
                'Financial summary retrieved successfully',
                [
                    'period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ],
                    'revenue' => $revenue,
                    'expenditure' => $expenditure,
                    'profit' => $profit,
                    'profit_margin' => $profitMargin,
                ]
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
