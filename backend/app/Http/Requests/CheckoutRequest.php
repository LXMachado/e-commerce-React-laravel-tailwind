<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Cart operations are allowed for both guests and authenticated users
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'cart_id' => 'nullable|exists:carts,id',
            'success_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cart_id.exists' => 'Selected cart does not exist',
            'success_url.url' => 'Success URL must be a valid URL',
            'cancel_url.url' => 'Cancel URL must be a valid URL',
        ];
    }
}