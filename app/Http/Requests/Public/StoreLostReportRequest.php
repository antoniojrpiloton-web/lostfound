<?php

namespace App\Http\Requests\Public;

use App\Models\ItemReport;
use Illuminate\Foundation\Http\FormRequest;

class StoreLostReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ItemReport::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:60'],
            'distinguishing_features' => ['nullable', 'string', 'max:1000'],
            'occurred_date' => ['required', 'date', 'before_or_equal:today'],
            'occurred_time' => ['nullable', 'date_format:H:i'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'reward_offered' => ['sometimes', 'boolean'],
            'reward_details' => ['nullable', 'string', 'max:255'],
            'prefer_in_app' => ['sometimes', 'boolean'],
            'prefer_email' => ['sometimes', 'boolean'],
            'prefer_phone' => ['sometimes', 'boolean'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'photos' => ['nullable', 'array', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
