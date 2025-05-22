<?php

namespace App\Http\Requests;

use App\Models\Admin;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends BaseFormRequest
{
    private $targetUser;
    private $targetUserType;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $authUser = $this->user();
        if (!$authUser) {
            return false;
        }

        $authUserType = match ($authUser->getTable()) {
            'admins' => 'admin',
            'residents' => 'resident',
            default => 'user'
        };

        // Get target user
        $userId = $this->route('userId');
        if (!$userId) {
            $this->targetUser = $authUser;
            $this->targetUserType = $authUserType;
        } else {
            $this->targetUser = $this->findUserById($userId);
            if (!$this->targetUser) {
                return false;
            }

            $this->targetUserType = match ($this->targetUser->getTable()) {
                'admins' => 'admin',
                'residents' => 'resident',
                default => 'user'
            };
        }

        return $this->canUpdateProfile($authUser, $authUserType, $this->targetUser, $this->targetUserType);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        if (!$this->targetUser) {
            return [];
        }

        switch ($this->targetUserType) {
            case 'user':
                return $this->getUserRules();
            case 'resident':
                return $this->getResidentRules();
            case 'admin':
                return $this->getAdminRules();
            default:
                return [];
        }
    }

    private function getUserRules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($this->targetUser->id)],
        ];
    }

    private function getResidentRules(): array
    {
        return [
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('residents', 'username')->ignore($this->targetUser->id)],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('residents', 'email')->ignore($this->targetUser->id)],
            'phone_number' => ['sometimes', 'string'],
            'age' => ['sometimes', 'integer', 'min:0'],
            'gender' => ['sometimes', 'in:male,female'],
            'profile_image' => ['sometimes', 'nullable', 'file', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }

    private function getAdminRules(): array
    {
        $rules = [
            'username' => ['sometimes', 'string', 'max:255', Rule::unique('admins', 'username')->ignore($this->targetUser->id)],
            'email' => ['sometimes', 'email', Rule::unique('admins', 'email')->ignore($this->targetUser->id)],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'string'],
            'age' => ['sometimes', 'integer', 'min:18'],
            'gender' => ['sometimes', 'in:male,female'],
            'salary' => ['sometimes', 'numeric', 'min:0'],
        ];

        // Only super admins can change roles
        $authUser = $this->user();
        if ($authUser && $authUser->getTable() === 'admins' && $authUser->role === 'super_admin') {
            $rules['role'] = ['sometimes', 'in:admin,super_admin'];
        }

        return $rules;
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email address is already taken.',
            'username.unique' => 'This username is already taken.',
            'age.min' => 'Age must be at least 18 for admins.',
            'gender.in' => 'Gender must be either male or female.',
            'profile_image.image' => 'Profile image must be a valid image file.',
            'profile_image.max' => 'Profile image must not exceed 2MB.',
            'profile_image.mimes' => 'Profile image must be a JPEG, PNG, JPG, or GIF file.',
        ];
    }

    private function findUserById($userId)
    {
        // Try to find user in each table
        $user = User::find($userId);
        if ($user) return $user;

        $resident = Resident::find($userId);
        if ($resident) return $resident;

        $admin = Admin::find($userId);
        if ($admin) return $admin;

        return null;
    }

    private function canUpdateProfile($authUser, $authUserType, $targetUser, $targetUserType): bool
    {
        // Users can only update their own profile
        if ($authUserType === 'user') {
            return $authUser->id === $targetUser->id && $targetUserType === 'user';
        }

        // Residents can only update their own profile
        if ($authUserType === 'resident') {
            return $authUser->id === $targetUser->id && $targetUserType === 'resident';
        }

        // Admins can update themselves, residents, and normal users
        if ($authUserType === 'admin' && $authUser->role === 'admin') {
            if ($targetUserType === 'admin') {
                // Regular admin can only update their own admin profile
                return $authUser->id === $targetUser->id;
            }
            // Can update residents and users
            return in_array($targetUserType, ['resident', 'user']);
        }

        // Super admins can update all profiles
        if ($authUserType === 'admin' && $authUser->role === 'super_admin') {
            return true;
        }

        return false;
    }

    public function getTargetUser()
    {
        return $this->targetUser;
    }

    public function getTargetUserType()
    {
        return $this->targetUserType;
    }
}
