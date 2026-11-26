<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Audit\AuditService;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        public AuditService $auditService,
        public MasterDataCacheService $masterDataCache,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::query()
            ->with('parent')
            ->orderBy('sort_order')
            ->orderBy('slug')
            ->paginate(25);

        $parentCategories = Category::query()
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        return view('admin.masterdata.categories', compact('categories', 'parentCategories'));
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $category = Category::query()->create([
            'parent_id' => $validated['parent_id'] ?? null,
            'slug' => strtolower($validated['slug']),
            'labels' => [
                'en' => $validated['label_en'],
                'fil' => $validated['label_fil'],
            ],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->masterDataCache->forgetCategories();

        $this->auditService->record(
            action: 'masterdata.category_created',
            auditable: $category,
            newValues: $category->only(['slug', 'labels', 'parent_id', 'is_active']),
            actor: $request->user(),
            request: $request,
        );

        return redirect()->route('admin.categories.index')->with('status', __('Category created.'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $validated = $request->validated();
        $oldValues = $category->only(['slug', 'labels', 'parent_id', 'sort_order', 'is_active']);

        $category->update([
            'parent_id' => $validated['parent_id'] ?? null,
            'slug' => strtolower($validated['slug']),
            'labels' => [
                'en' => $validated['label_en'],
                'fil' => $validated['label_fil'],
            ],
            'sort_order' => (int) ($validated['sort_order'] ?? $category->sort_order),
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : $category->is_active,
        ]);

        $this->masterDataCache->forgetCategories();

        $this->auditService->record(
            action: 'masterdata.category_updated',
            auditable: $category,
            oldValues: $oldValues,
            newValues: $category->only(['slug', 'labels', 'parent_id', 'sort_order', 'is_active']),
            actor: $request->user(),
            request: $request,
        );

        return redirect()->route('admin.categories.index')->with('status', __('Category updated.'));
    }
}
