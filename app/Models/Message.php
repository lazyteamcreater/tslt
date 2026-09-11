<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'guest_id',
        'guest_name',
        'reply_to_id',
        'type',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'reply_to_id' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(
            Message::class,
            'reply_to_id'
        );
    }

    public function senderData(): array
{
    if ($this->user) {
        return [
            'type' => 'user',

            'user_id' =>
                $this->user->id,

            'guest_id' => null,

            'name' =>
                $this->user->name,

            'avatar_url' =>
                $this->user->avatar_url,

            'role' =>
                $this->user->role,
        ];
    }

    return [
        'type' => 'guest',

        'user_id' => null,

        'guest_id' =>
            $this->guest_id,

        'name' =>
            $this->guest_name
            ?: 'ဧည့်သည်',

        'avatar_url' => null,

        'role' => 'guest',
    ];
}
}
