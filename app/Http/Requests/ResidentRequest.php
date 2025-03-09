<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResidentRequest extends FormRequest
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
        $rules = [
            'username' => ['string', 'max:255'],
            'first_name' => ['string', 'max:255'],
            'last_name' => ['string', 'max:255'],
            'email' => ['email'],
            'password' => ['string', 'min:8'],
            'phone_number' => ['string'],
            'age' => ['integer', 'min:0'],
            'gender' => ['in:male,female'],
            'status' => ['sometimes', 'in:active,inactive'],
            'house_id' => ['nullable', 'exists:houses,id'],
            'apartment_id' => ['nullable', 'exists:apartments,id'],
        ];

        if ($this->isMethod('POST')) {
            // Add required validation for store
            $rules['username'][] = 'required';
            $rules['first_name'][] = 'required';
            $rules['last_name'][] = 'required';
            $rules['email'][] = 'required';
            $rules['email'][] = 'unique:residents';
            $rules['password'][] = 'required';
            $rules['phone_number'][] = 'required';
            $rules['age'][] = 'required';
            $rules['gender'][] = 'required';
        } else {
            // Add sometimes validation for update
            $rules['username'][] = 'sometimes';
            $rules['first_name'][] = 'sometimes';
            $rules['last_name'][] = 'sometimes';
            $rules['email'][] = 'sometimes';
            $rules['email'][] = 'unique:residents,email,' . $this->resident?->id;
            $rules['password'][] = 'sometimes';
            $rules['phone_number'][] = 'sometimes';
            $rules['age'][] = 'sometimes';
            $rules['gender'][] = 'sometimes';
        }

        return $rules;
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
