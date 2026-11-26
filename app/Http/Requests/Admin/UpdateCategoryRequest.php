<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {

        $category = $this->route('category');

        return $category !== null && ($this->user()?->can('update', $category) ?? false);
    }

    public function rules(): array
    {

        $category = $this->route('category');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn([$category->id])],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('categories', 'slug')->ignore($category->id)],
            'label_en' => ['required', 'string', 'max:120'],
            'label_fil' => ['required', 'string', 'max:120'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
