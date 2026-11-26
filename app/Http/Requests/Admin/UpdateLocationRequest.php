<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {

        $location = $this->route('location');

        return $location !== null && ($this->user()?->can('update', $location) ?? false);
    }

    public function rules(): array
    {

        $location = $this->route('location');

        return [
            'barangay_id' => ['required', 'integer', 'exists:barangays,id'],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique('locations', 'name')
                    ->where(fn ($query) => $query->where('barangay_id', $this->integer('barangay_id')))
                    ->ignore($location->id),
            ],
            'type' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
