<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        /*
         * Login user ဖြစ်ရင် Sanctum Bearer Token ကနေသိမယ်။
         */
        $authenticatedUser =
            $request->user('sanctum');

        /*
         * Guest device identity.
         */
        $guestId =
            $request->header('X-Guest-Id');

        $isMine = false;

        if (
            $authenticatedUser &&
            $this->user_id ===
                $authenticatedUser->id
        ) {
            $isMine = true;
        }

        if (
            !$this->user_id &&
            $guestId &&
            $this->guest_id === $guestId
        ) {
            $isMine = true;
        }

        return [
            'id' => $this->id,

            'type' => $this->type,

            'body' => $this->body,

            'sender' =>
                $this->senderData(),

            'reply_to' =>
                $this->replyTo
                    ? [
                        'id' =>
                            $this->replyTo->id,

                        'body' =>
                            $this->replyTo->body,

                        'sender' =>
                            $this->replyTo
                                ->senderData(),
                    ]
                    : null,

            'is_mine' =>
                $isMine,

            'created_at' =>
                $this->created_at
                    ?->toISOString(),

            'updated_at' =>
                $this->updated_at
                    ?->toISOString(),
        ];
    }
}
