<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Location::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('locations', 'name')->where(fn ($query) => $query->where('barangay_id', $this->integer('barangay_id'))),
            ],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
