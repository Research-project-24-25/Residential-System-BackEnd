<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Throwable;

class ServiceController extends Controller
{
    /**
     * Display a listing of the services.
     *
     * @param Request $request
     * @return ResourceCollection|JsonResponse
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);

            $services = Service::query()
                ->when($request->user() && $request->user()->getTable() !== 'admins', function ($query) {
                    // Only show active services to non-admin users
                    return $query->where('is_active', true);
                })
                ->sort($request)
                ->paginate($perPage);

            return ServiceResource::collection($services);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get filtered services
     * 
     * @param ServiceRequest $request
     * @return ResourceCollection|JsonResponse
     */
    public function filter(ServiceRequest $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);

            $services = Service::query()
                ->when($request->user() && $request->user()->getTable() !== 'admins', function ($query) {
                    // Only show active services to non-admin users
                    return $query->where('is_active', true);
                })
                ->filter($request)
                ->sort($request)
                ->paginate($perPage);

            return ServiceResource::collection($services);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created service in storage.
     *
     * @param ServiceRequest $request
     * @return JsonResponse
     */
    public function store(ServiceRequest $request): JsonResponse
    {
        try {
            // Only admins can create services
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can create services');
            }

            $validated = $request->validated();
            $service = Service::create($validated);

            return $this->createdResponse(
                'Service created successfully',
                new ServiceResource($service)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified service.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);

            // Non-admin users can only view active services
            if ($request->user()->getTable() !== 'admins' && !$service->is_active) {
                return $this->forbiddenResponse('Service not available');
            }

            // Count active requests if admin
            if ($request->user()->getTable() === 'admins') {
                $service->loadCount('activeRequests');
            }

            return $this->successResponse(
                'Service retrieved successfully',
                new ServiceResource($service)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified service in storage.
     *
     * @param ServiceRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(ServiceRequest $request, int $id): JsonResponse
    {
        try {
            // Only admins can update services
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can update services');
            }

            $service = Service::findOrFail($id);
            $validated = $request->validated();

            $service->update($validated);

            return $this->successResponse(
                'Service updated successfully',
                new ServiceResource($service)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified service from storage.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can delete services
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can delete services');
            }

            $service = Service::findOrFail($id);

            // Check if there are any service requests associated with this service
            if ($service->serviceRequests()->exists()) {
                return $this->errorResponse(
                    'Cannot delete service because it has associated service requests',
                    422
                );
            }

            $service->delete();

            return $this->successResponse('Service deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Toggle service active status
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse 
     */
    public function toggleActive(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can toggle service status
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can change service status');
            }

            $service = Service::findOrFail($id);
            $service->is_active = !$service->is_active;
            $service->save();

            $status = $service->is_active ? 'activated' : 'deactivated';

            return $this->successResponse(
                "Service {$status} successfully",
                new ServiceResource($service)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
