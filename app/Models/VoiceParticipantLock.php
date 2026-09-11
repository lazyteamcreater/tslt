<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoiceParticipantLock extends Model
{
    protected $fillable = [
        'voice_session_id',
        'user_id',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'voice_session_id' => 'integer',
            'user_id' => 'integer',
            'is_locked' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(
            VoiceSession::class,
            'voice_session_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
