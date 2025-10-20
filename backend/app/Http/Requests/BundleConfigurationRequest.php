<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class BundleConfigurationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow both authenticated and guest users to create configurations
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'configuration' => 'required|array',
            'configuration.espresso_module' => 'boolean',
            'configuration.filter_attachment' => 'boolean',
            'configuration.fan_accessory' => 'boolean',
            'configuration.solar_panel_size' => 'required|in:10W,15W,20W',
            'name' => 'nullable|string|max:255',
        ];

        // If this is an update request (has ID in route), add additional validation
        if ($this->route('id')) {
            $rules['name'] = 'sometimes|nullable|string|max:255';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'configuration.required' => 'Configuration options are required.',
            'configuration.array' => 'Configuration must be an object with option selections.',
            'configuration.solar_panel_size.required' => 'Please select a solar panel size.',
            'configuration.solar_panel_size.in' => 'Solar panel size must be 10W, 15W, or 20W.',
            'name.max' => 'Configuration name cannot exceed 255 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'configuration.espresso_module' => 'espresso module option',
            'configuration.filter_attachment' => 'filter attachment option',
            'configuration.fan_accessory' => 'fan accessory option',
            'configuration.solar_panel_size' => 'solar panel size',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure configuration is properly formatted
        if ($this->has('configuration') && is_array($this->configuration)) {
            // Set default values for missing options
            $configuration = $this->configuration;

            if (!isset($configuration['espresso_module'])) {
                $configuration['espresso_module'] = false;
            }
            if (!isset($configuration['filter_attachment'])) {
                $configuration['filter_attachment'] = false;
            }
            if (!isset($configuration['fan_accessory'])) {
                $configuration['fan_accessory'] = false;
            }

            $this->merge([
                'configuration' => $configuration,
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Allow all valid configurations including base kit (solar panel only)
            // The solar panel size is already validated as required in rules()
            // Optional modules are truly optional and can all be false
        });
    }
}
