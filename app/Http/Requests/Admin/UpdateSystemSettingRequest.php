<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {

        $setting = $this->route('setting');

        return $setting !== null && ($this->user()?->can('update', $setting) ?? false);
    }

    public function rules(): array
    {
        return [
            'value' => ['required'],
        ];
    }
}
