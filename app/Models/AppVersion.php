<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = [
        'platform',
        'latest_version',
        'latest_build',
        'minimum_version',
        'minimum_build',
        'force_update',
        'download_url',
        'message',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latest_build' => 'integer',
            'minimum_build' => 'integer',
            'force_update' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
