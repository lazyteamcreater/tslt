<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'media_category_id',
        'type',
        'title',
        'description',
        'speaker',
        'source_url',
        'thumbnail_url',
        'duration_seconds',
        'view_count',
        'sort_order',
        'is_active',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'media_category_id' => 'integer',
            'duration_seconds' => 'integer',
            'view_count' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            MediaCategory::class,
            'media_category_id'
        );
    }
}
