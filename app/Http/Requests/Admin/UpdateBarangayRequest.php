<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBarangayRequest extends FormRequest
{
    public function authorize(): bool
    {

        $barangay = $this->route('barangay');

        return $barangay !== null && ($this->user()?->can('update', $barangay) ?? false);
    }

    public function rules(): array
    {

        $barangay = $this->route('barangay');

        return [
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('barangays', 'code')->ignore($barangay->id)],
            'name' => ['required', 'string', 'max:100'],
            'municipality' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
