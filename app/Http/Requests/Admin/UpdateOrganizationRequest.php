<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {

        $organization = $this->route('organization');

        return $organization !== null && ($this->user()?->can('update', $organization) ?? false);
    }

    public function rules(): array
    {

        $organization = $this->route('organization');

        return [
            'type' => ['required', Rule::enum(OrganizationType::class)],
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('organizations', 'code')->ignore($organization->id)],
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
