<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\Voice\VoiceGlobalMicLockChanged;
use App\Events\Voice\VoiceParticipantKicked;
use App\Events\Voice\VoiceParticipantMicLockChanged;
use App\Events\Voice\VoiceParticipantMuted;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VoiceParticipantLock;
use App\Models\VoiceSession;
use App\Services\LiveKit\LiveKitModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceModerationController extends Controller
{
    public function __construct(
        private readonly LiveKitModerationService
            $moderationService
    ) {
    }

    /**
     * Lock all normal users.
     *
     * Admin + Streamer မထိ။
     */
    public function lockAll(
        Request $request
    ): JsonResponse {
        if (!$request->user()->canManageVoice()) {
            return $this->forbidden();
        }

        $session =
            $this->activeSession();

        if (!$session) {
            return $this->noActiveRoom();
        }

        $session->update([
            'mic_locked' => true,
        ]);

        $this->moderationService
            ->applyGlobalMicLock(
                $session,
                true
            );

        broadcast(
            new VoiceGlobalMicLockChanged(
                sessionId: $session->id,
                micLocked: true
            )
        )->toOthers();

        return response()->json([
            'message' =>
                'Normal User များ၏ မိုက်ကို Lock လုပ်ပြီးပါပြီ။',

            'data' => [
                'session_id' =>
                    $session->id,

                'mic_locked' =>
                    true,
            ],
        ]);
    }

    /**
     * Unlock all normal users.
     *
     * Individual Lock ရှိသူတွေကို
     * မဖွင့်ပေးဘူး။
     */
    public function unlockAll(
        Request $request
    ): JsonResponse {
        if (!$request->user()->canManageVoice()) {
            return $this->forbidden();
        }

        $session =
            $this->activeSession();

        if (!$session) {
            return $this->noActiveRoom();
        }

        $session->update([
            'mic_locked' => false,
        ]);

        $this->moderationService
            ->applyGlobalMicLock(
                $session,
                false
            );

        broadcast(
            new VoiceGlobalMicLockChanged(
                sessionId: $session->id,
                micLocked: false
            )
        )->toOthers();

        return response()->json([
            'message' =>
                'Global Mic Lock ကို ဖြုတ်ပြီးပါပြီ။',

            'data' => [
                'session_id' =>
                    $session->id,

                'mic_locked' =>
                    false,
            ],
        ]);
    }

    /**
     * Lock one participant.
     *
     * Streamer + User ရ
     * Admin မရ။
     */
    public function lockParticipant(
        Request $request,
        User $user
    ): JsonResponse {
        if (!$request->user()->canManageVoice()) {
            return $this->forbidden();
        }

        if ($user->isAdmin()) {
            return response()->json([
                'message' =>
                    'Admin ကို Mic Lock လုပ်၍မရပါ။',
            ], 422);
        }

        $session =
            $this->activeSession();

        if (!$session) {
            return $this->noActiveRoom();
        }

        VoiceParticipantLock::updateOrCreate(
            [
                'voice_session_id' =>
                    $session->id,

                'user_id' =>
                    $user->id,
            ],
            [
                'is_locked' =>
                    true,
            ]
        );

        $this->moderationService
            ->applyParticipantMicLock(
                $session,
                $user,
                true
            );

        broadcast(
            new VoiceParticipantMicLockChanged(
                sessionId: $session->id,
                userId: $user->id,
                micLocked: true
            )
        )->toOthers();

        return response()->json([
            'message' =>
                'အသုံးပြုသူ၏ မိုက်ကို Lock လုပ်ပြီးပါပြီ။',

            'data' => [
                'session_id' =>
                    $session->id,

                'user_id' =>
                    $user->id,

                'mic_locked' =>
                    true,
            ],
        ]);
    }

    /**
     * Unlock one participant.
     */
    public function unlockParticipant(
        Request $request,
        User $user
    ): JsonResponse {
        if (!$request->user()->canManageVoice()) {
            return $this->forbidden();
        }

        if ($user->isAdmin()) {
            return response()->json([
                'message' =>
                    'Admin တွင် Individual Mic Lock မရှိပါ။',
            ], 422);
        }

        $session =
            $this->activeSession();

        if (!$session) {
            return $this->noActiveRoom();
        }

        VoiceParticipantLock::query()
            ->where(
                'voice_session_id',
                $session->id
            )
            ->where(
                'user_id',
                $user->id
            )
            ->update([
                'is_locked' =>
                    false,
            ]);

        $this->moderationService
            ->applyParticipantMicLock(
                $session,
                $user,
                false
            );

        broadcast(
            new VoiceParticipantMicLockChanged(
                sessionId: $session->id,
                userId: $user->id,
                micLocked: false
            )
        )->toOthers();

        return response()->json([
            'message' =>
                'အသုံးပြုသူ၏ Mic Lock ကို ဖြုတ်ပြီးပါပြီ။',

            'data' => [
                'session_id' =>
                    $session->id,

                'user_id' =>
                    $user->id,

                'mic_locked' =>
                    false,
            ],
        ]);
    }

    /**
     * Mute current microphone track.
     *
     * User က နောက်မှ mic ပြန်ဖွင့်နိုင်တယ်။
     */
    public function muteParticipant(
        Request $request,
        User $user
    ): JsonResponse {
        if (!$request->user()->canManageVoice()) {
            return $this->forbidden();
        }

        if ($user->isAdmin()) {
            return response()->json([
                'message' =>
                    'Admin ကို Mute လုပ်၍မရပါ။',
            ], 422);
        }

        $session =
            $this->activeSession();

        if (!$session) {
            return $this->noActiveRoom();
        }

        $muted =
            $this->moderationService
                ->muteParticipantMicrophone(
                    $session,
                    $user
                );

        if (!$muted) {
            return response()->json([
                'message' =>
                    'အသုံးပြုသူ၏ ဖွင့်ထားသော Microphone မတွေ့ပါ။',
            ], 422);
        }

        broadcast(
            new VoiceParticipantMuted(
                sessionId: $session->id,
                userId: $user->id
            )
        )->toOthers();

        return response()->json([
            'message' =>
                'အသုံးပြုသူ၏ မိုက်ကို Mute လုပ်ပြီးပါပြီ။',

            'data' => [
                'session_id' =>
                    $session->id,

                'user_id' =>
                    $user->id,
            ],
        ]);
    }

    /**
     * Kick participant.
     *
     * Temporary kick only.
     */
    public function kickParticipant(
        Request $request,
        User $user
    ): JsonResponse {
        if (!$request->user()->canManageVoice()) {
            return $this->forbidden();
        }

        if ($user->isAdmin()) {
            return response()->json([
                'message' =>
                    'Admin ကို Voice Room မှ ဖယ်ရှား၍မရပါ။',
            ], 422);
        }

        $session =
            $this->activeSession();

        if (!$session) {
            return $this->noActiveRoom();
        }

        $kicked =
            $this->moderationService
                ->kickParticipant(
                    $session,
                    $user
                );

        if (!$kicked) {
            return response()->json([
                'message' =>
                    'အသုံးပြုသူကို Voice Room မှ ဖယ်ရှား၍မရပါ။',
            ], 422);
        }

        broadcast(
            new VoiceParticipantKicked(
                sessionId: $session->id,
                userId: $user->id
            )
        )->toOthers();

        return response()->json([
            'message' =>
                'အသုံးပြုသူကို Voice Room မှ ဖယ်ရှားပြီးပါပြီ။',

            'data' => [
                'session_id' =>
                    $session->id,

                'user_id' =>
                    $user->id,
            ],
        ]);
    }

    private function activeSession(): ?VoiceSession
    {
        return VoiceSession::query()
            ->where(
                'status',
                'active'
            )
            ->latest('id')
            ->first();
    }

    private function forbidden(): JsonResponse
    {
        return response()->json([
            'message' =>
                'Voice Room ကို စီမံခန့်ခွဲခွင့်မရှိပါ။',
        ], 403);
    }

    private function noActiveRoom(): JsonResponse
    {
        return response()->json([
            'message' =>
                'လက်ရှိ Voice Room မရှိပါ။',
        ], 404);
    }
}
