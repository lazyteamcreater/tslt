<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoiceSession extends Model
{
    protected $fillable = [
        'host_user_id',
        'room_name',
        'title',
        'status',
        'mic_locked',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'mic_locked' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function host(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'host_user_id'
        );
    }

    public function participantLocks(): HasMany
    {
        return $this->hasMany(
            VoiceParticipantLock::class
        );
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
