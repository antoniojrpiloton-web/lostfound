<?php

namespace App\Http\Requests\Admin;

use App\Enums\CustodyStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminFoundReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('report.encode_on_behalf') ?? false;
    }

    public function rules(): array
    {
        $canApproveException = $this->user()?->hasPermission('report.photo_exception') ?? false;
        $hasException = $canApproveException && $this->filled('photo_exception_reason');

        return [
            'item_name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:60'],
            'distinguishing_features' => ['nullable', 'string', 'max:1000'],
            'occurred_date' => ['required', 'date', 'before_or_equal:today'],
            'occurred_time' => ['nullable', 'date_format:H:i'],
            'barangay_id' => ['nullable', 'integer', 'exists:barangays,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'custody_organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            'custody_stage' => ['nullable', 'string', Rule::in([CustodyStage::AtBooth->value, CustodyStage::AtStation->value])],
            'booth_location' => ['nullable', 'string', 'max:180'],
            'finder_name' => ['required', 'string', 'max:150'],
            'finder_contact' => ['nullable', 'string', 'max:40'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'photo_exception_reason' => $canApproveException ? ['nullable', 'string', 'max:255'] : ['prohibited'],
            'photos' => $hasException ? ['nullable', 'array', 'max:3'] : ['required', 'array', 'min:1', 'max:3'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
