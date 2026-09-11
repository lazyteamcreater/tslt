<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'type' => $this->type,

            'title' => $this->title,

            'description' => $this->description,

            'speaker' => $this->speaker,

            'source_url' => $this->source_url,

            'thumbnail_url' => $this->thumbnail_url,

            'duration_seconds' => $this->duration_seconds,

            'view_count' => $this->view_count,

            'category' => $this->whenLoaded(
                'category',
                function () {
                    if (!$this->category) {
                        return null;
                    }

                    return [
                        'id' => $this->category->id,
                        'name' => $this->category->name,
                    ];
                }
            ),

            'published_at' => $this->published_at?->toISOString(),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
