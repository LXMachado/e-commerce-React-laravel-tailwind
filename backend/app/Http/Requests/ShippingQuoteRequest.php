<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShippingQuoteRequest extends FormRequest
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
            'postcode' => 'required|string|size:4|regex:/^\d{4}$/',
            'weight' => 'nullable|numeric|min:0',
            'cart_id' => 'nullable|exists:carts,id',
            'method_code' => 'nullable|string|exists:shipping_methods,code',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'postcode.required' => 'Australian postcode is required',
            'postcode.size' => 'Australian postcode must be exactly 4 digits',
            'postcode.regex' => 'Australian postcode must contain only digits',
            'weight.numeric' => 'Weight must be a valid number',
            'weight.min' => 'Weight cannot be negative',
            'cart_id.exists' => 'Invalid cart ID',
            'method_code.exists' => 'Invalid shipping method code',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'postcode' => 'Australian postcode',
            'cart_id' => 'cart ID',
            'method_code' => 'shipping method',
        ];
    }
}
