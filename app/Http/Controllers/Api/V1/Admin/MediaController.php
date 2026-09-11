<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreMediaRequest;
use App\Http\Requests\Api\V1\Admin\UpdateMediaRequest;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MediaController extends Controller
{
    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $type = $request->query('type');

        $media = Media::query()
            ->with('category:id,name')
            ->when(
                in_array($type, ['audio', 'video'], true),
                fn ($query) => $query->where(
                    'type',
                    $type
                )
            )
            ->orderByDesc('id')
            ->paginate(30);

        return MediaResource::collection(
            $media
        );
    }

    public function store(
        StoreMediaRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $media = Media::create([
            'media_category_id' =>
                $validated['media_category_id'] ?? null,

            'type' => $validated['type'],

            'title' => $validated['title'],

            'description' =>
                $validated['description'] ?? null,

            'speaker' =>
                $validated['speaker'] ?? null,

            'source_url' =>
                $validated['source_url'],

            'thumbnail_url' =>
                $validated['thumbnail_url'] ?? null,

            'duration_seconds' =>
                $validated['duration_seconds'] ?? null,

            'view_count' => 0,

            'sort_order' =>
                $validated['sort_order'] ?? 0,

            'is_active' =>
                $validated['is_active'] ?? true,

            'published_at' =>
                $validated['published_at'] ?? now(),
        ]);

        $media->load('category:id,name');

        return response()->json([
            'message' => 'Media ထည့်ပြီးပါပြီ။',

            'data' => [
                'media' => new MediaResource(
                    $media
                ),
            ],
        ], 201);
    }

    public function update(
        UpdateMediaRequest $request,
        Media $medium
    ): JsonResponse {
        $medium->update(
            $request->validated()
        );

        $medium->refresh();

        $medium->load('category:id,name');

        return response()->json([
            'message' => 'Media ပြင်ပြီးပါပြီ။',

            'data' => [
                'media' => new MediaResource(
                    $medium
                ),
            ],
        ]);
    }

    public function destroy(
        Media $medium
    ): JsonResponse {
        $medium->delete();

        return response()->json([
            'message' => 'Media ဖျက်ပြီးပါပြီ။',
        ]);
    }
}
