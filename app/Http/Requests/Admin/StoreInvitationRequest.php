<?php

namespace App\Http\Requests\Admin;

use App\Models\OrganizationInvitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', OrganizationInvitation::class) ?? false;
    }

    public function rules(): array
    {
        $isSuperAdmin = $this->user()?->isPlatformSuperAdmin() ?? false;

        $allowedRoles = $isSuperAdmin
            ? ['platform_super_admin', 'institution_admin', 'institution_staff']
            : ['institution_staff'];

        return [
            'email' => ['required', 'email', 'max:255'],
            'role_key' => ['required', 'string', Rule::in($allowedRoles)],
            'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'expires_in_days' => ['sometimes', 'integer', 'min:1', 'max:30'],
        ];
    }
}
