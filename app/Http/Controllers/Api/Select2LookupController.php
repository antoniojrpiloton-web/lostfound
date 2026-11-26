<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Category;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Select2LookupController extends Controller
{
    private const PER_PAGE = 15;

    public function categories(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        $query = Category::query()
            ->with('parent')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('slug');

        if ($term !== '') {
            $like = '%'.strtolower($term).'%';
            $query->where(function ($builder) use ($like): void {
                $builder->whereRaw('LOWER(slug) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(CAST(labels AS CHAR)) LIKE ?', [$like]);
            });
        }

        $page = $query->paginate(self::PER_PAGE);

        return response()->json([
            'results' => $page->getCollection()->map(fn (Category $category): array => [
                'id' => $category->id,
                'text' => ($category->parent ? $category->parent->label().' — ' : '').$category->label(),
            ])->values()->all(),
            'pagination' => ['more' => $page->hasMorePages()],
        ]);
    }

    public function barangays(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        $query = Barangay::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like);
            });
        }

        $page = $query->paginate(self::PER_PAGE);

        return response()->json([
            'results' => $page->getCollection()->map(fn (Barangay $barangay): array => [
                'id' => $barangay->id,
                'text' => $barangay->name.' ('.$barangay->municipality.')',
            ])->values()->all(),
            'pagination' => ['more' => $page->hasMorePages()],
        ]);
    }

    public function locations(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        $query = Location::query()
            ->with('barangay')
            ->where('is_active', true)
            ->orderBy('name');

        if ($request->filled('barangay_id')) {
            $query->where('barangay_id', $request->integer('barangay_id'));
        }

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('type', 'like', $like)
                    ->orWhere('description', 'like', $like);
            });
        }

        $page = $query->paginate(self::PER_PAGE);

        return response()->json([
            'results' => $page->getCollection()->map(fn (Location $location): array => [
                'id' => $location->id,
                'text' => $location->name.($location->barangay ? ' ('.$location->barangay->name.')' : ''),
            ])->values()->all(),
            'pagination' => ['more' => $page->hasMorePages()],
        ]);
    }

    public function organizations(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));
        $query = Organization::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like);
            });
        }

        $page = $query->paginate(self::PER_PAGE);

        return response()->json([
            'results' => $page->getCollection()->map(fn (Organization $org): array => [
                'id' => $org->id,
                'text' => $org->name.' ['.$org->type->label().']',
            ])->values()->all(),
            'pagination' => ['more' => $page->hasMorePages()],
        ]);
    }
}
