<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidentRequest;
use App\Http\Resources\ResidentResource;
use App\Models\Resident;
use App\Models\Property;
use App\Services\PropertyResidentService;
use App\Traits\HandlesFileUploads;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Throwable;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ResidentController extends Controller
{
    use HandlesFileUploads;

    public function __construct(private PropertyResidentService $service) {}

    public function index(Request $request): ResourceCollection|JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);

            $residents = Resident::query()
                ->with(['properties'])
                ->sort($request)
                ->search($request)
                ->paginate($perPage);

            return ResidentResource::collection($residents);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function filter(ResidentRequest $request): ResourceCollection|JsonResponse
    {
        try {
            $residents = Resident::query()
                ->with(['properties'])
                ->filter($request)
                ->search($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return ResidentResource::collection($residents);
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $resident = Resident::with(['properties'])->findOrFail($id);

            return $this->successResponse(
                'Resident retrieved successfully',
                new ResidentResource($resident)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function store(ResidentRequest $request): JsonResponse
    {
        try {
            // Get validated data
            $validated = $request->validated();

            // Create resident data array with proper fields
            $residentData = [
                'username' => $validated['username'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone_number' => $validated['phone_number'],
                'age' => $validated['age'],
                'gender' => $validated['gender'],
                'created_by' => $request->user()->id,
            ];

            // Handle profile image if provided
            if ($request->hasFile('profile_image')) {
                $residentData['profile_image'] = $this->handleResidentProfileImage($request->file('profile_image'));
            } else {
                $residentData['profile_image'] = null;
            }

            // Create resident only - property attachment will be handled by PropertyResidentController
            $resident = Resident::create($residentData);

            // Send welcome notification to the new resident
            $resident->notify(new \App\Notifications\NewResidentWelcomeNotification($resident));

            return $this->createdResponse(
                'Resident created successfully. Use PropertyResidentController to attach to properties.',
                new ResidentResource($resident)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    // Update the update method to focus only on resident data
    public function update($id, ResidentRequest $request): JsonResponse
    {
        try {
            $resident = Resident::with(['properties'])->findOrFail($id);
            $validated = $request->validated();

            // Remove property relationship fields from resident updates
            $updateData = array_diff_key($validated, array_flip([
                'property_id',
                'relationship_type',
                'sale_price',
                'ownership_share',
                'monthly_rent',
                'start_date',
                'end_date'
            ]));

            // Hash the password if it's provided
            if (isset($updateData['password'])) {
                $updateData['password'] = Hash::make($updateData['password']);
            }

            // Handle profile image if provided
            if ($request->hasFile('profile_image')) {
                $updateData['profile_image'] = $this->replaceResidentProfileImage(
                    $request->file('profile_image'),
                    $resident->getRawOriginal('profile_image')
                );
            } elseif (isset($updateData['profile_image']) && $updateData['profile_image'] === null) {
                // If profile_image is explicitly set to null, remove existing image
                $this->removeResidentProfileImage($resident->getRawOriginal('profile_image'));
                $updateData['profile_image'] = null;
            }

            $resident->update($updateData);

            return $this->successResponse(
                'Resident updated successfully. Use PropertyResidentController to manage property relationships.',
                new ResidentResource($resident->fresh(['properties']))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $resident = Resident::findOrFail($id);

            // Remove profile image if exists
            $this->removeResidentProfileImage($resident->getRawOriginal('profile_image'));

            $resident->delete();

            return $this->successResponse('Resident deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    public function restore(int $id): JsonResponse
    {
        return $this->restoreModel(Resident::class, $id);
    }

    public function trashed(Request $request): JsonResponse
    {
        return $this->getTrashedModels(Resident::class, function ($query) use ($request) {
            if ($request->has('sort')) {
                $query->sort($request);
            }
        });
    }

    public function forceDelete(int $id): JsonResponse
    {
        return $this->forceDeleteModel(Resident::class, $id);
    }
}
