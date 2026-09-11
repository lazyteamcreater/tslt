<?php

namespace App\Events\Voice;

use App\Http\Resources\Api\V1\VoiceSessionResource;
use App\Models\VoiceSession;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceSessionStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VoiceSession $session
    ) {
        $this->session->loadMissing(
            'host:id,name,avatar_url,role'
        );
    }

    public function broadcastOn(): array
{
    return [
        new PrivateChannel('voice'),
    ];
}

    public function broadcastAs(): string
    {
        return 'voice.started';
    }

    public function broadcastWith(): array
    {
        return [
            'session' => (
                new VoiceSessionResource($this->session)
            )->resolve(),
        ];
    }
}
