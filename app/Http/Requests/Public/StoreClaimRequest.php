<?php

namespace App\Http\Requests\Public;

use App\Models\Claim;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClaimRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Claim::class) ?? false;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'ownership_description' => ['required', 'string', 'min:15', 'max:2000'],
            'distinguishing_details' => ['required', 'string', 'min:5', 'max:1500'],
            'lost_report_id' => [
                'nullable',
                'integer',
                Rule::exists('item_reports', 'id')->where(fn ($query) => $query->where('reporter_user_id', $userId)->where('type', 'lost')),
            ],
            'evidence' => ['nullable', 'array', 'max:3'],
            'evidence.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'evidence_description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
