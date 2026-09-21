<?php

namespace App\Services\LiveKit;

use Agence104\LiveKit\RoomServiceClient;
use App\Models\User;
use App\Models\VoiceParticipantLock;
use App\Models\VoiceSession;
use Livekit\ParticipantPermission;
use Livekit\TrackSource;
use Throwable;

class LiveKitModerationService
{
    private RoomServiceClient $roomService;

    public function __construct()
    {
        $this->roomService =
            new RoomServiceClient(
                config('services.livekit.url'),
                config('services.livekit.api_key'),
                config('services.livekit.api_secret')
            );
    }

    /**
     * Global Lock / Unlock.
     *
     * Admin     -> မထိ
     * Streamer  -> မထိ
     * User      -> ထိ
     */
    public function applyGlobalMicLock(
        VoiceSession $session,
        bool $locked
    ): void {
        $response =
            $this->roomService
                ->listParticipants(
                    $session->room_name
                );

        foreach (
            $response->getParticipants()
            as $participant
        ) {
            $userId =
                $this->participantUserId(
                    $participant->getIdentity()
                );

            if (!$userId) {
                continue;
            }

            $user = User::query()
                ->find($userId);

            if (!$user) {
                continue;
            }

            /*
             * Admin + Streamer ကို
             * Global Lock မသက်ရောက်။
             */
            if (
                $user->isAdmin() ||
                $user->isStreamer()
            ) {
                continue;
            }

            /*
             * Lock All
             */
            if ($locked) {
                $this->setCanPublish(
                    $session,
                    $user,
                    false
                );

                continue;
            }

            /*
             * Unlock All လုပ်တဲ့အခါ
             * Individual Lock ရှိနေသေးရင်
             * mic permission ပြန်မပေးရ။
             */
            $individualLocked =
                $this->isIndividuallyLocked(
                    $session,
                    $user
                );

            $this->setCanPublish(
                $session,
                $user,
                !$individualLocked
            );
        }
    }

    /**
     * Individual Mic Lock / Unlock.
     *
     * Admin    -> lock မလုပ်နိုင်
     * Streamer -> lock လုပ်နိုင်
     * User     -> lock လုပ်နိုင်
     */
    public function applyParticipantMicLock(
        VoiceSession $session,
        User $user,
        bool $locked
    ): void {
        if ($user->isAdmin()) {
            return;
        }

        /*
         * Streamer က Global Lock မထိပေမယ့်
         * Individual Lock ထိတယ်။
         */
        if ($user->isStreamer()) {
            $canPublish = !$locked;
        } else {
            /*
             * Normal User:
             *
             * Individual unlock လုပ်ပေမယ့်
             * Global Lock ရှိနေသေးရင်
             * publish မရသေးဘူး။
             */
            $canPublish =
                !$locked &&
                !$session->mic_locked;
        }

        $this->setCanPublish(
            $session,
            $user,
            $canPublish
        );
    }

    /**
     * Mute currently published microphone.
     *
     * Permission ကိုမပိတ်ဘူး။
     * လက်ရှိ microphone track ကိုပဲ mute လုပ်မယ်။
     */
    public function muteParticipantMicrophone(
        VoiceSession $session,
        User $user
    ): bool {
        if ($user->isAdmin()) {
            return false;
        }

        try {
            $participant =
                $this->roomService
                    ->getParticipant(
                        $session->room_name,
                        (string) $user->id
                    );

            foreach (
                $participant->getTracks()
                as $track
            ) {
                if (
                    $track->getSource() !==
                    TrackSource::MICROPHONE
                ) {
                    continue;
                }

                $this->roomService
                    ->mutePublishedTrack(
                        $session->room_name,
                        (string) $user->id,
                        $track->getSid(),
                        true
                    );

                return true;
            }

            return false;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * Kick participant from current room.
     *
     * NOTE:
     * Kick က temporary ပါ။
     * User က token အသစ်ယူပြီး ပြန်ဝင်နိုင်သေးတယ်။
     */
    public function kickParticipant(
        VoiceSession $session,
        User $user
    ): bool {
        if ($user->isAdmin()) {
            return false;
        }

        try {
            $this->roomService
                ->removeParticipant(
                    $session->room_name,
                    (string) $user->id
                );

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * End LiveKit room.
     *
     * deleteRoom() လုပ်တာနဲ့
     * participants အားလုံး disconnect ဖြစ်မယ်။
     */
    public function endRoom(
        VoiceSession $session
    ): void {
        try {
            $this->roomService
                ->deleteRoom(
                    $session->room_name
                );
        } catch (Throwable $exception) {
            /*
             * LiveKit room မရှိတော့တာမျိုးဖြစ်လည်း
             * Laravel session ကို ended
             * ပြောင်းနိုင်အောင် exception ကို
             * request failure မဖြစ်စေဘူး။
             */
            report($exception);
        }
    }

    /**
     * Current participants.
     */
    public function participants(
        VoiceSession $session
    ): array {
        try {
            $response =
                $this->roomService
                    ->listParticipants(
                        $session->room_name
                    );
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        /*
         * LiveKit participant အားလုံး၏ user id ကို အရင်စုယူပြီး
         * users နှင့် mic locks ကို query တစ်ကြိမ်စီဖြင့် ယူမည်။
         * ယခင်လို participant တစ်ယောက်စီအတွက် query မပစ်တော့ပါ။
         */
        $liveKitParticipants = [];
        $userIds = [];

        foreach (
            $response->getParticipants()
            as $participant
        ) {
            $userId =
                $this->participantUserId(
                    $participant->getIdentity()
                );

            if (!$userId) {
                continue;
            }

            $liveKitParticipants[] = [
                'user_id' => $userId,
                'participant' => $participant,
            ];
            $userIds[] = $userId;
        }

        if ($userIds === []) {
            return [];
        }

        $userIds = array_values(
            array_unique($userIds)
        );

        $users = User::query()
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $lockedUserIds = VoiceParticipantLock::query()
            ->where(
                'voice_session_id',
                $session->id
            )
            ->whereIn('user_id', $userIds)
            ->where('is_locked', true)
            ->pluck('user_id')
            ->mapWithKeys(
                fn ($userId): array => [
                    (int) $userId => true,
                ]
            );

        $participants = [];

        foreach ($liveKitParticipants as $entry) {
            $userId = $entry['user_id'];
            $participant = $entry['participant'];
            $user = $users->get($userId);

            if (!$user) {
                continue;
            }

            $isMicOn = false;
            $isMicMuted = false;

            foreach (
                $participant->getTracks()
                as $track
            ) {
                if (
                    $track->getSource() !==
                    TrackSource::MICROPHONE
                ) {
                    continue;
                }

                $isMicMuted =
                    $track->getMuted();

                $isMicOn =
                    !$isMicMuted;

                break;
            }

            $individualLocked =
                $user->isAdmin()
                    ? false
                    : $lockedUserIds
                        ->has($userId);

            /*
             * Effective Mic Lock
             */
            if ($user->isAdmin()) {
                $effectiveLocked = false;
            } elseif ($individualLocked) {
                $effectiveLocked = true;
            } elseif ($user->isStreamer()) {
                $effectiveLocked = false;
            } else {
                $effectiveLocked =
                    $session->mic_locked;
            }

            $participants[] = [
                'user_id' =>
                    $user->id,

                'name' =>
                    $user->name,

                'avatar_url' =>
                    $user->avatar_url,

                'role' =>
                    $user->role,

                'is_host' =>
                    $session->host_user_id ===
                    $user->id,

                'is_mic_on' =>
                    $isMicOn,

                'is_mic_muted' =>
                    $isMicMuted,

                'is_mic_locked' =>
                    $effectiveLocked,

                'is_individually_locked' =>
                    $individualLocked,

                'is_global_mic_locked' =>
                    $user->role === 'user'
                        ? (bool) $session->mic_locked
                        : false,

                /*
                 * Admin App မဟုတ်ဘဲ
                 * User App Voice UI မှာ
                 * moderation controls ပြဖို့။
                 */
                'can_manage_room' =>
                    $user->canManageVoice(),
            ];
        }

        return $participants;
    }

    /**
     * Change LiveKit publish permission.
     */
    private function setCanPublish(
        VoiceSession $session,
        User $user,
        bool $canPublish
    ): void {
        try {
            $permission =
                (new ParticipantPermission())
                    ->setCanSubscribe(true)
                    ->setCanPublish(
                        $canPublish
                    );

            $this->roomService
                ->updateParticipant(
                    $session->room_name,
                    (string) $user->id,
                    null,
                    $permission
                );
        } catch (Throwable $exception) {
            /*
             * User က room ကထွက်သွားတာနဲ့
             * moderation request တိုက်ဆိုင်ရင်
             * API တစ်ခုလုံး fail မဖြစ်စေဘူး။
             */
            report($exception);
        }
    }

    /**
     * Check DB individual mic lock.
     */
    private function isIndividuallyLocked(
        VoiceSession $session,
        User $user
    ): bool {
        return VoiceParticipantLock::query()
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
    }

    /**
     * LiveKit identity = Laravel User ID.
     */
    private function participantUserId(
        string $identity
    ): ?int {
        if (
            !ctype_digit($identity)
        ) {
            return null;
        }

        $userId = (int) $identity;

        return $userId > 0
            ? $userId
            : null;
    }
}
