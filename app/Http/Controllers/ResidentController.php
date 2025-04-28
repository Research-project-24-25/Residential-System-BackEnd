<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResidentRequest;
use App\Http\Resources\ResidentResource;
use App\Models\Resident;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Services\ResidentPropertyService;
use Illuminate\Http\UploadedFile;
use Throwable;

class ResidentController extends Controller
{
    public function __construct(private ResidentPropertyService $service) {}

    /**
     * Get all residents with optional pagination
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 10);

            $residents = Resident::query()
                ->with(['properties'])
                ->sort($request)
                ->paginate($perPage);

            return $this->successResponse(
                'Residents retrieved successfully',
                ResidentResource::collection($residents)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get filtered residents
     * 
     * @param ResidentRequest $request
     * @return JsonResponse
     */
    public function filter(ResidentRequest $request): JsonResponse
    {
        try {
            $residents = Resident::query()
                ->with(['properties'])
                ->filter($request)
                ->sort($request)
                ->paginate($request->get('per_page', 10));

            return $this->successResponse(
                'Residents retrieved successfully',
                ResidentResource::collection($residents)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get a single resident
     * 
     * @param int $id
     * @return JsonResponse
     */
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

    /**
     * Create a new resident and attach to property
     * 
     * @param ResidentRequest $request
     * @return JsonResponse
     */
    public function store(ResidentRequest $request): JsonResponse
    {
        try {
            $property = Property::findOrFail($request->property_id);

            // Get validated data
            $validated = $request->validated();

            // Create resident data array with proper fields
            $residentData = [
                'username' => $validated['username'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'phone_number' => $validated['phone_number'],
                'age' => $validated['age'],
                'gender' => $validated['gender'],
                'created_by' => $request->user()->id, // Get the authenticated admin's ID
            ];

            // Handle profile image if provided
            if ($request->hasFile('profile_image')) {
                $residentData['profile_image'] = $this->handleProfileImage($request->file('profile_image'));
            }

            // Prepare pivot data
            $pivotData = [
                'relationship_type' => $validated['relationship_type'],
                'sale_price' => $validated['sale_price'] ?? null,
                'ownership_share' => $validated['ownership_share'] ?? null,
                'monthly_rent' => $validated['monthly_rent'] ?? null,
                'start_date' => $validated['start_date'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
            ];

            $resident = $this->service->createAndAttach($residentData, $property, $pivotData);

            return $this->createdResponse(
                'Resident created successfully',
                new ResidentResource($resident)
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update a resident
     * 
     * @param int $id
     * @param ResidentRequest $request
     * @return JsonResponse
     */
    public function update($id, ResidentRequest $request): JsonResponse
    {
        try {
            $resident = Resident::with(['properties'])->findOrFail($id);
            $validated = $request->validated();

            // Remove fields that should not be updated directly
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
                // Remove old profile image if exists
                if (!empty($resident->profile_image)) {
                    $this->removeOldProfileImage($resident->profile_image);
                }

                $updateData['profile_image'] = $this->handleProfileImage($request->file('profile_image'));
            }

            $resident->update($updateData);

            // Update property relationship if property_id is provided
            if (isset($validated['property_id'])) {
                $property = Property::findOrFail($validated['property_id']);

                $pivotData = [
                    'relationship_type' => $validated['relationship_type'],
                    'sale_price' => $validated['sale_price'] ?? null,
                    'ownership_share' => $validated['ownership_share'] ?? null,
                    'monthly_rent' => $validated['monthly_rent'] ?? null,
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                ];

                $this->service->updatePropertyRelationship($resident, $property, $pivotData);
            }

            return $this->successResponse(
                'Resident updated successfully',
                new ResidentResource($resident->fresh(['properties']))
            );
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete a resident
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $resident = Resident::findOrFail($id);

            // Remove profile image if exists
            if (!empty($resident->profile_image)) {
                $this->removeOldProfileImage($resident->profile_image);
            }

            $resident->delete();

            return $this->successResponse('Resident deleted successfully');
        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle uploading resident profile image
     * 
     * @param UploadedFile $image
     * @return string
     */
    private function handleProfileImage($image): string
    {
        $filename = time() . '_' . $image->getClientOriginalName();
        $image->move(public_path('resident-images'), $filename);
        return 'resident-images/' . $filename;
    }

    /**
     * Remove old profile image
     * 
     * @param string $imagePath
     * @return void
     */
    private function removeOldProfileImage($imagePath): void
    {
        $path = public_path($imagePath);
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
