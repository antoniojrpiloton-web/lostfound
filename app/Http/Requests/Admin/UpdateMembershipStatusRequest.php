<?php

namespace App\Http\Requests\Admin;

use App\Enums\MembershipStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMembershipStatusRequest extends FormRequest
{
    public function authorize(): bool
    {

        $membership = $this->route('membership');

        return $membership !== null && ($this->user()?->can('update', $membership) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(MembershipStatus::class)],
        ];
    }
}
