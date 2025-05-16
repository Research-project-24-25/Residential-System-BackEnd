<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminRequest;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Throwable;

class AdminController extends Controller
{
    /**
     * Display a listing of admins.
     */
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            // Only super admins can view all admins
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super administrators can access this resource');
            }

            $perPage = $request->get('per_page', 10);
            $admins = Admin::query()->sort($request)->paginate($perPage);

            return AdminResource::collection($admins);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Filter and paginate admins.
     */
    public function filter(AdminRequest $request): ResourceCollection|JsonResponse
    {
        try {
            // Only super admins can filter admins
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super administrators can access this resource');
            }

            $perPage = $request->get('per_page', 10);
            $admins = Admin::query()
                ->filter($request)
                ->sort($request)
                ->paginate($perPage);

            return AdminResource::collection($admins);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified admin.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            // Only super admins can view admin details
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super administrators can access this resource');
            }

            $admin = Admin::findOrFail($id);

            return $this->successResponse(
                'Admin retrieved successfully',
                new AdminResource($admin)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Register a new admin.
     * Moved from AdminAuthController.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super admins can create admin accounts.');
            }

            $validated = $request->validate([
                'username' => ['required', 'string', 'max:255', 'unique:admins'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:admins'],
                'password' => ['required', 'confirmed', Password::defaults()],
                'role' => ['required', 'in:admin,super_admin'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'phone_number' => ['required', 'string'],
                'age' => ['required', 'integer', 'min:18'],
                'gender' => ['required', 'in:male,female'],
                'salary' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            ]);
            $validated['password'] = Hash::make($validated['password']);

            $admin = Admin::create($validated);
            $token = $admin->createToken('admin-token')->plainTextToken;

            return $this->createdResponse(
                'Admin registered successfully',
                [
                    'admin' => new AdminResource($admin),
                    'token' => $token
                ]
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified admin.
     */
    public function update(AdminRequest $request, int $id): JsonResponse
    {
        try {
            // Only super admins can update admins
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super administrators can update admin accounts');
            }

            $admin = Admin::findOrFail($id);
            $validated = $request->validated();

            // Hash the password if it's provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $admin->update($validated);

            return $this->successResponse(
                'Admin updated successfully',
                new AdminResource($admin)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified admin.
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            // Only super admins can delete admins
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super administrators can delete admin accounts');
            }

            // Prevent super admin from deleting themselves
            if ($id === $request->user()->id) {
                return $this->errorResponse('You cannot delete your own account', 422);
            }

            $admin = Admin::findOrFail($id);

            // Prevent deleting the last super admin
            if ($admin->isSuperAdmin() && Admin::where('role', 'super_admin')->count() <= 1) {
                return $this->errorResponse('Cannot delete the last super admin account', 422);
            }

            $admin->delete();

            return $this->successResponse('Admin deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display a listing of soft-deleted admins.
     */
    public function trashed(Request $request): JsonResponse
    {
        try {
            // Only super admins can view trashed admins
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super administrators can access this resource');
            }

            return $this->getTrashedModels(Admin::class, function ($query) use ($request) {
                if ($request->has('sort')) {
                    $query->sort($request);
                }
            });
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Restore a soft-deleted admin.
     */
    public function restore(int $id, Request $request): JsonResponse
    {
        try {
            // Only super admins can restore admins
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super administrators can restore admin accounts');
            }

            return $this->restoreModel(Admin::class, $id);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Permanently delete a soft-deleted admin.
     */
    public function forceDelete(int $id, Request $request): JsonResponse
    {
        try {
            // Only super admins can force delete admins
            if (!$request->user()->isSuperAdmin()) {
                return $this->forbiddenResponse('Only super administrators can permanently delete admin accounts');
            }

            // Prevent force deleting themselves
            if ($id === $request->user()->id) {
                return $this->errorResponse('You cannot permanently delete your own account', 422);
            }

            return $this->forceDeleteModel(Admin::class, $id);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get the current admin's profile.
     * Moved from AdminAuthController.
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            return $this->successResponse(
                'Profile retrieved successfully',
                new AdminResource($request->user())
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
