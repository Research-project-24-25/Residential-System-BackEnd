<?php

namespace App\Http\Controllers;

use App\Http\Requests\MaintenanceRequest;
use App\Http\Resources\MaintenanceResource;
use App\Models\Maintenance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class MaintenanceController extends Controller
{
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);

            $maintenances = Maintenance::query()
                // Only show active maintenance types to residents, show all to admins
                ->when($request->user()->getTable() === 'residents', function ($query) {
                    return $query->where('is_active', true);
                })
                ->sort($request)
                ->search($request)
                ->paginate($perPage);

            return MaintenanceResource::collection($maintenances);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function filter(MaintenanceRequest $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);

            $maintenances = Maintenance::query()
                ->when($request->user() && $request->user()->getTable() !== 'admins', function ($query) {
                    // Only show active maintenance types to non-admin users
                    return $query->where('is_active', true);
                })
                ->filter($request)
                ->sort($request)
                ->search($request)
                ->paginate($perPage);

            return MaintenanceResource::collection($maintenances);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(MaintenanceRequest $request): JsonResponse
    {
        try {
            // Only admins can create maintenance types
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can create maintenance types');
            }

            $validated = $request->validated();
            $maintenance = Maintenance::create($validated);

            return $this->createdResponse(
                'Maintenance type created successfully',
                new MaintenanceResource($maintenance)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $maintenance = Maintenance::findOrFail($id);

            // Non-admin users can only view active maintenance types
            if ($request->user()->getTable() !== 'admins' && !$maintenance->is_active) {
                return $this->forbiddenResponse('Maintenance type not available');
            }

            // Count active requests if admin
            if ($request->user()->getTable() === 'admins') {
                $maintenance->loadCount('activeRequests');
            }

            return $this->successResponse(
                'Maintenance type retrieved successfully',
                new MaintenanceResource($maintenance)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(MaintenanceRequest $request, int $id): JsonResponse
    {
        try {
            // Only admins can update maintenance types
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can update maintenance types');
            }

            $maintenance = Maintenance::findOrFail($id);
            $validated = $request->validated();

            $maintenance->update($validated);

            return $this->successResponse(
                'Maintenance type updated successfully',
                new MaintenanceResource($maintenance)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can delete maintenance types
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can delete maintenance types');
            }

            $maintenance = Maintenance::findOrFail($id);

            // Check if there are any maintenance requests associated with this type
            if ($maintenance->maintenanceRequests()->exists()) {
                return $this->errorResponse(
                    'Cannot delete maintenance type because it has associated maintenance requests',
                    422
                );
            }

            $maintenance->delete();

            return $this->successResponse('Maintenance type deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function restore(int $id): JsonResponse
    {
        return $this->restoreModel(Maintenance::class, $id);
    }

    public function trashed(Request $request): JsonResponse
    {
        return $this->getTrashedModels(Maintenance::class, function ($query) use ($request) {
            if ($request->has('sort')) {
                $query->sort($request);
            }
        });
    }

    public function forceDelete(int $id): JsonResponse
    {
        return $this->forceDeleteModel(Maintenance::class, $id);
    }

    public function toggleActive(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can toggle maintenance type status
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can change maintenance type status');
            }

            $maintenance = Maintenance::findOrFail($id);
            $maintenance->is_active = !$maintenance->is_active;
            $maintenance->save();

            $status = $maintenance->is_active ? 'activated' : 'deactivated';

            return $this->successResponse(
                "Maintenance type {$status} successfully",
                new MaintenanceResource($maintenance)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function categories(): JsonResponse
    {
        try {
            $categories = [
                'plumbing',
                'electrical',
                'hvac',
                'structural',
                'appliances',
                'landscaping',
                'painting',
                'other'
            ];

            return $this->successResponse(
                'Maintenance categories retrieved successfully',
                $categories
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
