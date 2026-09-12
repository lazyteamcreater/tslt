<?php

namespace App\Events\Voice;

use App\Models\VoiceSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoiceSessionEnded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public VoiceSession $session
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('voice-status'),
            new PrivateChannel('voice'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'voice.ended';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
        ];
    }
}
