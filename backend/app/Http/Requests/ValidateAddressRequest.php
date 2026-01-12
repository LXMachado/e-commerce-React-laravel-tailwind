<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'suburb' => 'required|string|max:255',
            'state' => 'required|string|size:3|alpha',
            'postcode' => 'required|string|size:4|regex:/^\d{4}$/',
            'country' => 'required|string|in:AU,Australia',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'address_line_1.required' => 'Street address is required',
            'suburb.required' => 'Suburb is required',
            'state.required' => 'State is required',
            'state.size' => 'State must be exactly 3 characters (e.g., NSW, VIC)',
            'state.alpha' => 'State must contain only letters',
            'postcode.required' => 'Postcode is required',
            'postcode.size' => 'Australian postcode must be exactly 4 digits',
            'postcode.regex' => 'Australian postcode must contain only digits',
            'country.required' => 'Country is required',
            'country.in' => 'Country must be Australia (AU)',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'address_line_1' => 'street address',
            'address_line_2' => 'address line 2',
            'state' => 'Australian state',
            'postcode' => 'Australian postcode',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize country to uppercase
        if ($this->has('country')) {
            $this->merge([
                'country' => strtoupper($this->input('country'))
            ]);
        }

        // Normalize state to uppercase
        if ($this->has('state')) {
            $this->merge([
                'state' => strtoupper($this->input('state'))
            ]);
        }
    }
}
