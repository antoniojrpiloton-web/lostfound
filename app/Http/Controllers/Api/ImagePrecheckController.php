<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\Reports\ImageValidationClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImagePrecheckController extends Controller
{
    public function __construct(public ImageValidationClient $validationClient) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $categorySlug = null;
        if (! empty($validated['category_id'])) {
            $categorySlug = Category::query()->whereKey((int) $validated['category_id'])->value('slug');
        }

        $outcome = $this->validationClient->validate($request->file('image'), is_string($categorySlug) ? $categorySlug : null);
        $checks = is_array($outcome['result']['checks'] ?? null) ? $outcome['result']['checks'] : [];
        $obj = is_array($checks['object_detection'] ?? null) ? $checks['object_detection'] : [];

        return response()->json([
            'status' => $outcome['status']->value,
            'messages' => $outcome['messages'],
            'metrics' => [
                'sharpness' => isset($checks['laplacian_variance']) ? round((float) $checks['laplacian_variance'], 1) : null,
                'object_confidence' => isset($obj['object_probability']) && $obj['object_probability'] !== null
                    ? (int) round(((float) $obj['object_probability']) * 100)
                    : null,
            ],
        ]);
    }
}
