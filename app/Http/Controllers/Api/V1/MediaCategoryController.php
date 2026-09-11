<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MediaCategoryResource;
use App\Models\MediaCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MediaCategoryController extends Controller
{
    public function index(
        Request $request
    ): AnonymousResourceCollection {
        $type = $request->query('type');

        $categories = MediaCategory::query()
            ->where('is_active', true)
            ->when(
                in_array($type, ['audio', 'video'], true),
                fn ($query) => $query->where('type', $type)
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return MediaCategoryResource::collection($categories);
    }
}
