<?php

namespace App\Events\Chat;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public Message $message
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $message = $this->message;

        return [
            'id' => $message->id,

            'type' => $message->type,

            'body' => $message->body,

            'sender' => $message->senderData(),

            'reply_to' => $message->replyTo
                ? [
                    'id' => $message->replyTo->id,

                    'body' => $message->replyTo->body,

                    'sender' => $message->replyTo
                        ->senderData(),
                ]
                : null,

            'created_at' => $message->created_at
                ?->toISOString(),
        ];
    }
}
