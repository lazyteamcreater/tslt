<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreMediaRequest;
use App\Http\Requests\Api\V1\Admin\UpdateMediaRequest;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Media::query()
            ->with('category');

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

            $query->where(function ($query) use ($search) {
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
            });
        }

        $media = $query
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'data' => collect($media->items())
                ->map(
                    fn (Media $medium): array =>
                        $this->mediaData($medium)
                )
                ->values(),

            'meta' => [
                'current_page' =>
                    $media->currentPage(),

                'last_page' =>
                    $media->lastPage(),

                'per_page' =>
                    $media->perPage(),

                'total' =>
                    $media->total(),
            ],
        ]);
    }

    public function store(
        StoreMediaRequest $request
    ): JsonResponse {
        $data = $this->prepareData(
            $request->validated()
        );

        $medium = Media::query()->create($data);

        $medium->load('category');

        return response()->json([
            'message' => 'Media ထည့်ပြီးပါပြီ။',
            'data' => $this->mediaData($medium),
        ], 201);
    }

    public function show(
        Media $medium
    ): JsonResponse {
        $medium->load('category');

        return response()->json([
            'data' => $this->mediaData($medium),
        ]);
    }

    public function update(
        UpdateMediaRequest $request,
        Media $medium
    ): JsonResponse {
        $data = $this->prepareData(
            $request->validated()
        );

        $medium->update($data);
        $medium->refresh();
        $medium->load('category');

        return response()->json([
            'message' => 'Media ပြင်ပြီးပါပြီ။',
            'data' => $this->mediaData($medium),
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

    private function prepareData(array $data): array
{
    $data['type'] = strtolower(
        trim((string) ($data['type'] ?? ''))
    );

    if ($data['type'] === 'video') {
        $orientation = strtolower(
            trim(
                (string) (
                    $data['video_orientation']
                    ?? 'landscape'
                )
            )
        );

        $data['video_orientation'] =
            $orientation === 'portrait'
                ? 'portrait'
                : 'landscape';

        /*
         * Thumbnail မပါလာမှ YouTube link/ID ကနေ
         * အလိုအလျောက်တည်ဆောက်ပါမယ်။
         */
        $thumbnail = trim(
            (string) ($data['thumbnail_url'] ?? '')
        );

        if ($thumbnail === '') {
            $youtubeId = $this->extractYoutubeId(
                (string) ($data['source_url'] ?? '')
            );

            $data['thumbnail_url'] = $youtubeId !== null
                ? "https://img.youtube.com/vi/{$youtubeId}/hqdefault.jpg"
                : null;
        }
    } else {
        $data['video_orientation'] = null;
    }

    $data['sort_order'] =
        (int) ($data['sort_order'] ?? 0);

    $data['is_active'] =
        array_key_exists('is_active', $data)
            ? (bool) $data['is_active']
            : true;

    return $data;
}

private function extractYoutubeId(
    string $source
): ?string {
    $source = trim($source);

    if ($source === '') {
        return null;
    }

    /*
     * YouTube ID သီးသန့်
     * ဥပမာ: cpmUr7a_1To
     */
    if (
        preg_match(
            '/^[a-zA-Z0-9_-]{11}$/',
            $source
        ) === 1
    ) {
        return $source;
    }

    $parts = parse_url($source);

    if ($parts === false) {
        return null;
    }

    $host = strtolower(
        (string) ($parts['host'] ?? '')
    );

    $host = preg_replace(
        '/^www\./',
        '',
        $host
    );

    $path = trim(
        (string) ($parts['path'] ?? ''),
        '/'
    );

    /*
     * youtu.be/VIDEO_ID
     */
    if ($host === 'youtu.be') {
        $segments = explode('/', $path);
        $videoId = $segments[0] ?? null;

        return $this->validYoutubeId($videoId);
    }

    $youtubeHosts = [
        'youtube.com',
        'm.youtube.com',
        'music.youtube.com',
    ];

    if (!in_array($host, $youtubeHosts, true)) {
        return null;
    }

    /*
     * youtube.com/watch?v=VIDEO_ID
     */
    if (($parts['query'] ?? '') !== '') {
        parse_str(
            (string) $parts['query'],
            $query
        );

        if (!empty($query['v'])) {
            return $this->validYoutubeId(
                (string) $query['v']
            );
        }
    }

    /*
     * youtube.com/shorts/VIDEO_ID
     * youtube.com/embed/VIDEO_ID
     * youtube.com/live/VIDEO_ID
     */
    $segments = array_values(
        array_filter(
            explode('/', $path),
            fn (string $segment): bool =>
                $segment !== ''
        )
    );

    if (count($segments) >= 2) {
        $type = strtolower($segments[0]);

        if (
            in_array(
                $type,
                ['shorts', 'embed', 'live'],
                true
            )
        ) {
            return $this->validYoutubeId(
                $segments[1]
            );
        }
    }

    return null;
}

private function validYoutubeId(
    mixed $videoId
): ?string {
    if (!is_string($videoId)) {
        return null;
    }

    $videoId = trim($videoId);

    return preg_match(
        '/^[a-zA-Z0-9_-]{11}$/',
        $videoId
    ) === 1
        ? $videoId
        : null;
}

    private function mediaData(
        Media $medium
    ): array {
        return [
            'id' => $medium->id,

            'media_category_id' =>
                $medium->media_category_id,

            'type' => $medium->type,

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
            'thumbnail_url' =>
                $medium->thumbnail_url,

            'duration_seconds' =>
                $medium->duration_seconds,

            'view_count' =>
                $medium->view_count,

            'sort_order' =>
                $medium->sort_order,

            'is_active' =>
                (bool) $medium->is_active,

            'published_at' =>
                $medium->published_at
                    ?->toISOString(),

            'created_at' =>
                $medium->created_at
                    ?->toISOString(),

            'updated_at' =>
                $medium->updated_at
                    ?->toISOString(),

            'category' =>
                $medium->category
                    ? [
                        'id' =>
                            $medium->category->id,

                        'name' =>
                            $medium->category->name,

                        'type' =>
                            $medium->category->type,

                        'image_url' =>
                            $medium->category
                                ->image_url,
                    ]
                    : null,
        ];
    }
}
