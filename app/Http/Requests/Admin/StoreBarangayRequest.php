<?php

namespace App\Http\Requests\Admin;

use App\Models\Barangay;
use Illuminate\Foundation\Http\FormRequest;

class StoreBarangayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Barangay::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:barangays,code'],
            'name' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
