<?php

namespace App\Events\Voice;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceParticipantKicked implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $sessionId,
        public int $userId
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('voice'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'voice.user.kicked';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' =>
                $this->sessionId,

            'user_id' =>
                $this->userId,
        ];
    }
}
