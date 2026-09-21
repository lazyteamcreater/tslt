<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    /**
     * Public Media List
     *
     * Examples:
     *
     * /api/v1/media?type=audio
     * /api/v1/media?type=video
     * /api/v1/media?category_id=1
     * /api/v1/media?search=တရား
     */
    public function index(Request $request): JsonResponse
    {
        $query = Media::query()
            ->with('category')
            ->where('is_active', true)
            ->where(function ($query) {
                $query
                    ->whereNull('published_at')
                    ->orWhere(
                        'published_at',
                        '<=',
                        now()
                    );
            });

        if (
            in_array(
                $request->input('type'),
                ['audio', 'video'],
                true
            )
        ) {
            $query->where(
                'type',
                $request->input('type')
            );
        }

        if ($request->filled('category_id')) {
            $query->where(
                'media_category_id',
                $request->integer('category_id')
            );
        }

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(
                function ($query) use ($search) {
                    $query
                        ->where(
                            'title',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'speaker',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'description',
                            'like',
                            "%{$search}%"
                        );
                }
            );
        }

        $media = $query
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => collect($media->items())
                ->map(
                    fn (Media $medium): array =>
                        $this->mediaData($medium)
                )
                ->values(),

            'meta' => [
                'per_page' => $media->perPage(),

                'next_cursor' => $media
                    ->nextCursor()
                    ?->encode(),

                'previous_cursor' => $media
                    ->previousCursor()
                    ?->encode(),
            ],
        ]);
    }

    /**
     * Public Media Detail
     */
    public function show(Media $medium): JsonResponse
    {
        $this->ensureAvailable($medium);

        $medium->load('category');

        return response()->json([
            'data' => $this->mediaData($medium),
        ]);
    }

    /**
     * Increase view count.
     */
    public function incrementView(
        Media $medium
    ): JsonResponse {
        $this->ensureAvailable($medium);

        $medium->increment('view_count');
        $medium->refresh();

        return response()->json([
            'message' =>
                'View count ကို မှတ်တမ်းတင်ပြီးပါပြီ။',

            'data' => [
                'id' => $medium->id,
                'view_count' => $medium->view_count,
            ],
        ]);
    }

    private function ensureAvailable(Media $medium): void
    {
        if (!$medium->is_active) {
            abort(404);
        }

        if (
            $medium->published_at &&
            $medium->published_at->isFuture()
        ) {
            abort(404);
        }
    }

    private function mediaData(Media $medium): array
    {
        return [
            'id' => $medium->id,
            'type' => $medium->type,

            /*
             * Audio ဆိုရင် null ဖြစ်ပါမယ်။
             * Video ဆိုရင် portrait/landscape ပြန်ပါမယ်။
             */
            'video_orientation' =>
                $medium->type === 'video'
                    ? (
                        $medium->video_orientation
                        ?: 'landscape'
                    )
                    : null,

            'title' => $medium->title,
            'description' => $medium->description,
            'speaker' => $medium->speaker,
            'source_url' => $medium->source_url,
            'thumbnail_url' => $medium->thumbnail_url,

            'duration_seconds' =>
                $medium->duration_seconds,

            'view_count' => $medium->view_count,
            'sort_order' => $medium->sort_order,

            'category' => $medium->category
                ? [
                    'id' => $medium->category->id,
                    'name' => $medium->category->name,
                    'type' => $medium->category->type,
                    'image_url' =>
                        $medium->category->image_url,
                ]
                : null,

            'published_at' => $medium
                ->published_at
                ?->toISOString(),

            'created_at' => $medium
                ->created_at
                ?->toISOString(),
        ];
    }
}
