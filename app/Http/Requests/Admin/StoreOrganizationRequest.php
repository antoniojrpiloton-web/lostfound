<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:organizations,code'],
            'name' => ['required', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'barangay_ids' => ['sometimes', 'array'],
            'barangay_ids.*' => ['integer', 'exists:barangays,id'],
        ];
    }
}
