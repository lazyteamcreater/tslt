<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\Voice\VoiceSessionEnded;
use App\Events\Voice\VoiceSessionStarted;
use App\Http\Controllers\Controller;
use App\Models\VoiceSession;
use App\Services\LiveKit\LiveKitModerationService;
use App\Services\LiveKit\LiveKitTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class VoiceSessionController extends Controller
{
    public function __construct(
        private readonly LiveKitTokenService
        $tokenService,

        private readonly LiveKitModerationService
        $moderationService
    ) {}

    /**
     * Public voice room status.
     */
    public function status(): JsonResponse
    {
        $session = VoiceSession::query()
            ->with(
                'host:id,name,avatar_url,role'
            )
            ->where(
                'status',
                'active'
            )
            ->latest('id')
            ->first();

        if (!$session) {
            return response()->json([
                'data' => [
                    'is_active' => false,
                    'session' => null,
                ],
            ]);
        }

        return response()->json([
            'data' => [
                'is_active' => true,

                'session' =>
                $this->sessionData(
                    $session
                ),
            ],
        ]);
    }

    /**
     * Admin / Streamer starts room.
     */
    public function start(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user->canStartVoice()) {
            return response()->json([
                'message' =>
                'Voice Room ဖွင့်ခွင့်မရှိပါ။',
            ], 403);
        }

        $validated = $request->validate([
            'title' => [
                'nullable',
                'string',
                'max:150',
            ],
        ]);

        /*
     * System တစ်ခုလုံးအတွက်
     * Voice Room start lock တစ်ခုတည်း။
     *
     * Production မှာ CACHE_STORE=redis
     * သုံးမယ်။
     */
        $lock = Cache::lock(
            'voice-room:lifecycle',
            15
        );

        if (!$lock->get()) {
            return response()->json([
                'message' =>
                'Voice Room ဖွင့်နေပါသည်။ ခဏစောင့်ပြီး ပြန်စမ်းပါ။',
            ], 409);
        }

        try {
            /*
         * Lock ရပြီးမှ active session ကို
         * မဖြစ်မနေ ပြန်စစ်ရမယ်။
         */
            $activeSession =
                VoiceSession::query()
                ->where(
                    'status',
                    'active'
                )
                ->latest('id')
                ->first();

            if ($activeSession) {
                return response()->json([
                    'message' =>
                    'Voice Room ဖွင့်ထားပြီးသားဖြစ်ပါသည်။',

                    'data' => [
                        'session' =>
                        $this->sessionData(
                            $activeSession
                                ->load('host')
                        ),
                    ],
                ], 409);
            }

            $session =
                VoiceSession::create([
                    'host_user_id' =>
                    $user->id,

                    'room_name' =>
                    'dhamma-' .
                        Str::uuid()->toString(),

                    'title' =>
                    $validated['title']
                        ?? null,

                    'status' =>
                    'active',

                    'mic_locked' =>
                    false,

                    'started_at' =>
                    now(),

                    'ended_at' =>
                    null,
                ]);

            $session->load('host');

            $token =
                $this->tokenService
                ->createTokenForUser(
                    $user,
                    $session
                );

            broadcast(
                new VoiceSessionStarted(
                    $session
                )
            )->toOthers();

            return response()->json([
                'message' =>
                'Voice Room ဖွင့်ပြီးပါပြီ။',

                'data' => [
                    'session' =>
                    $this->sessionData(
                        $session
                    ),

                    'livekit_url' =>
                    config(
                        'services.livekit.url'
                    ),

                    'token' =>
                    $token,
                ],
            ], 201);
        } finally {
            $lock->release();
        }
    }

    /**
     * Approved user joins active room.
     */
    public function join(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user->canJoinVoice()) {
            return response()->json([
                'message' =>
                'Voice Room ဝင်ရောက်ခွင့် မရှိသေးပါ။',
            ], 403);
        }

        $session =
            VoiceSession::query()
            ->with(
                'host:id,name,avatar_url,role'
            )
            ->where(
                'status',
                'active'
            )
            ->latest('id')
            ->first();

        if (!$session) {
            return response()->json([
                'message' =>
                'လက်ရှိ Voice Room မရှိပါ။',
            ], 404);
        }

        /*
         * TokenService က
         * Admin / Streamer / User permission
         * အားလုံးတွက်ပေးမယ်။
         */
        $token =
            $this->tokenService
            ->createTokenForUser(
                $user,
                $session
            );

        return response()->json([
            'message' =>
            'Voice Room ဝင်ရောက်နိုင်ပါပြီ။',

            'data' => [
                'session' =>
                $this->sessionData(
                    $session
                ),

                'livekit_url' =>
                config(
                    'services.livekit.url'
                ),

                'token' =>
                $token,
            ],
        ]);
    }

    /**
     * Admin / Streamer ends room.
     *
     * LiveKit room ကို delete လုပ်တာကြောင့်
     * participants အားလုံး disconnect ဖြစ်မယ်။
     */
    public function end(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user->canStartVoice()) {
            return response()->json([
                'message' =>
                'Voice Room ပိတ်ခွင့်မရှိပါ။',
            ], 403);
        }

        $lock = Cache::lock(
            'voice-room:lifecycle',
            15
        );

        if (!$lock->get()) {
            return response()->json([
                'message' =>
                'Voice Room ပိတ်နေပါသည်။',
            ], 409);
        }

        try {
            $session =
                VoiceSession::query()
                ->where(
                    'status',
                    'active'
                )
                ->latest('id')
                ->first();

            if (!$session) {
                return response()->json([
                    'message' =>
                    'လက်ရှိ Voice Room မရှိပါ။',
                ], 404);
            }

            /*
         * LiveKit room ကို delete လုပ်မယ်။
         *
         * deleteRoom() ကြောင့် room ထဲက
         * participant အားလုံး disconnect ဖြစ်မယ်။
         */
            $this->moderationService
                ->endRoom($session);

            /*
         * Laravel session ကို ended အဖြစ်
         * ပြောင်းမယ်။
         */
            $session->update([
                'status' =>
                'ended',

                'ended_at' =>
                now(),
            ]);

            $session->refresh();
            $session->load('host');

            broadcast(
                new VoiceSessionEnded(
                    $session
                )
            )->toOthers();

            return response()->json([
                'message' =>
                'Voice Room ပိတ်ပြီးပါပြီ။',

                'data' => [
                    'session' =>
                    $this->sessionData(
                        $session
                    ),
                ],
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Current LiveKit participant list.
     */
    public function participants(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if (!$user->canJoinVoice()) {
            return response()->json([
                'message' =>
                'Voice Room အသုံးပြုခွင့်မရှိပါ။',
            ], 403);
        }

        $session =
            VoiceSession::query()
            ->where(
                'status',
                'active'
            )
            ->latest('id')
            ->first();

        if (!$session) {
            return response()->json([
                'message' =>
                'လက်ရှိ Voice Room မရှိပါ။',
            ], 404);
        }

        $participants =
            $this->moderationService
            ->participants(
                $session
            );

        return response()->json([
            'data' => [
                'session_id' =>
                $session->id,

                'room_name' =>
                $session->room_name,

                'mic_locked' =>
                $session->mic_locked,

                'participant_count' =>
                count($participants),

                'participants' =>
                $participants,
            ],
        ]);
    }

    private function sessionData(
        VoiceSession $session
    ): array {
        return [
            'id' =>
            $session->id,

            'room_name' =>
            $session->room_name,

            'title' =>
            $session->title,

            'status' =>
            $session->status,

            'mic_locked' =>
            $session->mic_locked,

            'host' =>
            $session->host
                ? [
                    'id' =>
                    $session->host->id,

                    'name' =>
                    $session->host->name,

                    'avatar_url' =>
                    $session->host->avatar_url,

                    'role' =>
                    $session->host->role,
                ]
                : null,

            'started_at' =>
            $session->started_at
                ?->toISOString(),

            'ended_at' =>
            $session->ended_at
                ?->toISOString(),
        ];
    }
}
