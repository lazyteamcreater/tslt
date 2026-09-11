<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'avatar_url',
        'phone',
        'gender',
        'password',
        'role',
        'is_active',
        'is_approved',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Chat Messages
    |--------------------------------------------------------------------------
    */

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Voice Sessions
    |--------------------------------------------------------------------------
    */

    public function hostedVoiceSessions(): HasMany
    {
        return $this->hasMany(
            VoiceSession::class,
            'host_user_id'
        );
    }

    public function voiceParticipantLocks(): HasMany
    {
        return $this->hasMany(
            VoiceParticipantLock::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Firebase Device Tokens
    |--------------------------------------------------------------------------
    */

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(
            DeviceToken::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStreamer(): bool
    {
        return $this->role === 'streamer';
    }

    public function canJoinVoice(): bool
    {
        return
            $this->is_active &&
            $this->is_approved;
    }

    public function canStartVoice(): bool
    {
        return
            $this->canJoinVoice() &&
            in_array(
                $this->role,
                [
                    'admin',
                    'streamer',
                ],
                true
            );
    }

    public function canManageVoice(): bool
    {
        return
            $this->is_active &&
            $this->isAdmin();
    }
}
