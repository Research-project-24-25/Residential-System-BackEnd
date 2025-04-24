<?php

namespace App\Http\Requests;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;

class ResidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof Admin;
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
            
            'property_id'          => ['required', 'integer', 'exists:properties,id'],
            'relationship_type'    => ['required', 'in:buyer,co_buyer,renter'],
            'sale_price'           => ['nullable', 'numeric', 'min:0'],
            'ownership_share'      => ['nullable', 'numeric', 'between:0,100'],
            'monthly_rent'         => ['nullable', 'numeric', 'min:0'],
            'start_date'           => ['nullable', 'date'],
            'end_date'             => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }
}
