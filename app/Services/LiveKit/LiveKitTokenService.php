<?php

namespace App\Services\LiveKit;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Models\User;
use App\Models\VoiceParticipantLock;
use App\Models\VoiceSession;

class LiveKitTokenService
{
    public function createTokenForUser(
        User $user,
        VoiceSession $session
    ): string {
        return $this->createToken(
            user: $user,
            session: $session,
            canPublish: $this->canPublish(
                $user,
                $session
            )
        );
    }

    /**
     * Admin token.
     *
     * Admin ကို mic lock မလုပ်နိုင်ဘူး။
     */
    public function createAdminToken(
        User $user,
        VoiceSession $session
    ): string {
        return $this->createToken(
            user: $user,
            session: $session,
            canPublish: true
        );
    }

    /**
     * Determine whether this user can publish audio.
     */
    private function canPublish(
        User $user,
        VoiceSession $session
    ): bool {
        /*
         * Admin က Global Lock /
         * Individual Lock နှစ်မျိုးလုံး
         * မသက်ရောက်ဘူး။
         */
        if ($user->isAdmin()) {
            return true;
        }

        /*
         * Individual Lock ကို Global Lock
         * မစစ်ခင် အရင်စစ်ရမယ်။
         *
         * Streamer ကိုလည်း Admin က
         * individually lock လုပ်နိုင်တယ်။
         */
        $individualLocked =
            VoiceParticipantLock::query()
                ->where(
                    'voice_session_id',
                    $session->id
                )
                ->where(
                    'user_id',
                    $user->id
                )
                ->where(
                    'is_locked',
                    true
                )
                ->exists();

        if ($individualLocked) {
            return false;
        }

        /*
         * Streamer ကို Global Lock
         * မသက်ရောက်ဘူး။
         */
        if ($user->isStreamer()) {
            return true;
        }

        /*
         * Normal User ကို Global Lock
         * သက်ရောက်တယ်။
         */
        if ($session->mic_locked) {
            return false;
        }

        return true;
    }

    private function createToken(
        User $user,
        VoiceSession $session,
        bool $canPublish
    ): string {
        $tokenOptions =
            (new AccessTokenOptions())
                ->setIdentity(
                    (string) $user->id
                )
                ->setName(
                    $user->name
                );

        $grant =
            (new VideoGrant())
                ->setRoomJoin()
                ->setRoomName(
                    $session->room_name
                )
                ->setCanSubscribe(true)
                ->setCanPublish(
                    $canPublish
                );

        return (new AccessToken(
            config(
                'services.livekit.api_key'
            ),
            config(
                'services.livekit.api_secret'
            )
        ))
            ->init($tokenOptions)
            ->setGrant($grant)
            ->toJwt();
    }
}
