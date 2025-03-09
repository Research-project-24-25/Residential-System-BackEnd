<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResidentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof \App\Models\Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:residents'],
            'password' => ['required', 'string', 'min:8'],
            'phone_number' => ['required', 'string'],
            'age' => ['required', 'integer', 'min:0'],
            'gender' => ['required', 'in:male,female'],
            'status' => ['sometimes', 'in:active,inactive'],
            'house_id' => ['nullable', 'exists:houses,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
        ];
    }

    /**
     * Handle a passed validation attempt.
     */
    protected function passedValidation(): void
    {
        // Ensure resident is assigned to either a house or an apartment, not both
        if (($this->house_id ?? null) && ($this->apartment_id ?? null)) {
            $validator = $this->getValidatorInstance();
            $validator->errors()->add(
                'property_assignment',
                'A resident cannot be assigned to both a house and an apartment'
            );
            $this->failedValidation($validator);
        }
    }
}
