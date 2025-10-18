<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'required|string|max:100|unique:products',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0|gte:price',
            'cost_price' => 'nullable|numeric|min:0|lte:price',
            'track_inventory' => 'boolean',
            'weight_g' => 'nullable|integer|min:0',
            'dimensions' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'attribute_value_ids' => 'nullable|array',
            'attribute_value_ids.*' => 'exists:attribute_values,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'slug.required' => 'Product slug is required',
            'slug.unique' => 'This product slug is already taken',
            'sku.required' => 'Product SKU is required',
            'sku.unique' => 'This SKU is already taken',
            'price.required' => 'Product price is required',
            'compare_at_price.gte' => 'Compare at price must be greater than or equal to selling price',
            'cost_price.lte' => 'Cost price cannot be greater than selling price',
        ];
    }
}