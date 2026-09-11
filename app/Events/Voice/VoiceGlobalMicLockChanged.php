<?php

namespace App\Events\Voice;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceGlobalMicLockChanged implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public int $sessionId,
        public bool $micLocked
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
        return 'voice.mic.global-changed';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' =>
                $this->sessionId,

            'mic_locked' =>
                $this->micLocked,
        ];
    }
}
