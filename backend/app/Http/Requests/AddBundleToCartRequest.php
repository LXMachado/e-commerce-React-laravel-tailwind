<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddBundleToCartRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow both authenticated and guest users to add to cart
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => 'integer|min:1|max:10',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.integer' => 'Quantity must be a valid number.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity cannot exceed 10 per order.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default quantity if not provided
        if (!$this->has('quantity') || is_null($this->quantity)) {
            $this->merge([
                'quantity' => 1,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $configurationId = $this->route('id');

            // Check if the bundle configuration exists and is active
            $configuration = \App\Models\BundleConfiguration::where('id', $configurationId)
                                                           ->where('is_active', true)
                                                           ->first();

            if (!$configuration) {
                $validator->errors()->add('configuration', 'The selected bundle configuration is not available.');
                return;
            }

            // Check if the configuration is accessible to the current user
            if ($configuration->user_id && $configuration->user_id !== auth()->id()) {
                $validator->errors()->add('configuration', 'You do not have permission to add this configuration to cart.');
            }
        });
    }
}
