<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    /**
     * Get dashboard overview data
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Get recent activity for dashboard
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Get revenue statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
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

    /**
     * Get property statistics
     *
     * @return JsonResponse
     */
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

    /**
     * Get user statistics
     *
     * @return JsonResponse
     */
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
}
