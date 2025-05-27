<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UserController extends Controller
{
    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            // Only admins can view all users
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can access all users');
            }

            $perPage = $request->get('per_page', 10);

            $users = User::query()
                ->sort($request)
                ->search($request)
                ->paginate($perPage);

            return UserResource::collection($users);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function filter(UserRequest $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);

            $users = User::query()
                ->filter($request)
                ->sort($request)
                ->search($request)
                ->paginate($perPage);

            return UserResource::collection($users);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(UserRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Hash the password
            $validated['password'] = Hash::make($validated['password']);

            $user = User::create($validated);

            return $this->createdResponse(
                'User created successfully',
                new UserResource($user)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can view users
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can view users');
            }

            $user = User::findOrFail($id);

            return $this->successResponse(
                'User retrieved successfully',
                new UserResource($user)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function update(UserRequest $request, int $id): JsonResponse
    {
        try {
            // Only admins can update users
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can update users');
            }

            $user = User::findOrFail($id);
            $validated = $request->validated();

            // Hash the password if it's provided
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            return $this->successResponse(
                'User updated successfully',
                new UserResource($user)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can delete users
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can delete users');
            }

            $user = User::findOrFail($id);

            $user->delete();

            return $this->successResponse('User deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function restore(int $id): JsonResponse
    {
        return $this->restoreModel(User::class, $id);
    }

    public function trashed(Request $request): JsonResponse
    {
        return $this->getTrashedModels(User::class, function ($query) use ($request) {
            if ($request->has('sort')) {
                $query->sort($request);
            }
        });
    }

    public function forceDelete(int $id, Request $request): JsonResponse
    {
        try {
            // Only admins can permanently delete users
            if ($request->user()->getTable() !== 'admins') {
                return $this->forbiddenResponse('Only administrators can permanently delete users');
            }

            return $this->forceDeleteModel(User::class, $id);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }
}
