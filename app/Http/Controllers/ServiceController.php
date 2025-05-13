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
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();

            // Only admins can view all services
            if ($user->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can access all services');
            }

            $perPage = $request->get('per_page', 10);

            $services = Service::query()
                ->sort($request)
                ->paginate($perPage);

            return ServiceResource::collection($services);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function filter(ServiceRequest $request): ResourceCollection|JsonResponse
    {
        try {
            $user = $request->user();

            // Only admins can filter services
            if ($user->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can access all services');
            }

            $perPage = $request->get('per_page', 10);

            $services = Service::query()
                ->filter($request)
                ->sort($request)
                ->paginate($perPage);

            return ServiceResource::collection($services);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

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

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Only admins can view individual services directly
            if ($user->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can view service details');
            }

            $service = Service::findOrFail($id);

            // Count active properties if admin
            if ($user->getTable() === 'admins') {
                $service->loadCount('properties');
            }

            return $this->successResponse(
                'Service retrieved successfully',
                new ServiceResource($service)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

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

    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can delete services
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can delete services');
            }

            $service = Service::findOrFail($id);

            // Check if there are any property relationships with this service
            if ($service->properties()->exists()) {
                return $this->errorResponse(
                    'Cannot delete service because it is attached to properties',
                    422
                );
            }

            $service->delete();

            return $this->successResponse('Service deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

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
