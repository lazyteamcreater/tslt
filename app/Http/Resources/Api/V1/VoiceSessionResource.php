<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoiceSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'room_name' =>
                $this->room_name,

            'title' =>
                $this->title,

            'status' =>
                $this->status,

            'is_active' =>
                $this->is_active,

            'mic_locked' =>
                (bool) $this->mic_locked,

            'host' =>
                $this->host
                    ? [
                        'id' =>
                            $this->host->id,

                        'name' =>
                            $this->host->name,

                        'avatar_url' =>
                            $this->host->avatar_url,

                        'role' =>
                            $this->host->role,
                    ]
                    : null,

            'started_at' =>
                $this->started_at
                    ?->toISOString(),

            'ended_at' =>
                $this->ended_at
                    ?->toISOString(),
        ];
    }
}
