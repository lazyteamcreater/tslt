<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreMediaCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateMediaCategoryRequest;
use App\Http\Resources\Api\V1\MediaCategoryResource;
use App\Models\MediaCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MediaCategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $categories = MediaCategory::query()
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return MediaCategoryResource::collection(
            $categories
        );
    }

    public function store(
        StoreMediaCategoryRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $category = MediaCategory::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'image_url' => $validated['image_url'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Category ထည့်ပြီးပါပြီ။',
            'data' => [
                'category' => new MediaCategoryResource(
                    $category
                ),
            ],
        ], 201);
    }

    public function update(
        UpdateMediaCategoryRequest $request,
        MediaCategory $category
    ): JsonResponse {
        $category->update(
            $request->validated()
        );

        $category->refresh();

        return response()->json([
            'message' => 'Category ပြင်ပြီးပါပြီ။',
            'data' => [
                'category' => new MediaCategoryResource(
                    $category
                ),
            ],
        ]);
    }

    public function destroy(
        MediaCategory $category
    ): JsonResponse {
        $category->delete();

        return response()->json([
            'message' => 'Category ဖျက်ပြီးပါပြီ။',
        ]);
    }
}
