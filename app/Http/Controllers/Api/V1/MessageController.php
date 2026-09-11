<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\Chat\MessageDeleted;
use App\Events\Chat\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Chat\StoreMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $messages = Message::query()
            ->with([
                'user:id,name,avatar_url,role',
                'replyTo.user:id,name,avatar_url,role',
            ])
            ->latest('id')
            ->cursorPaginate(30);

        return MessageResource::collection(
            $messages
        );
    }

    public function store(
        StoreMessageRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        /*
         * Public route ဖြစ်ပေမယ့်
         * Bearer token ပါလာရင် Sanctum user ရမယ်။
         */
        $user = $request->user('sanctum');

        /*
         * Guest User
         */
        if (!$user) {
            if (
                empty($validated['guest_id']) ||
                empty($validated['guest_name'])
            ) {
                return response()->json([
                    'message' =>
                        'ဧည့်သည်အမည်နှင့် Device ID လိုအပ်ပါသည်။',
                ], 422);
            }
        }

        /*
         * Blocked registered user cannot chat.
         */
        if (
            $user &&
            !$user->is_active
        ) {
            return response()->json([
                'message' =>
                    'ဤအကောင့်ကို အသုံးပြုခွင့် ပိတ်ထားပါသည်။',
            ], 403);
        }

        $message = Message::create([
            'user_id' =>
                $user?->id,

            'guest_id' =>
                $user
                    ? null
                    : $validated['guest_id'],

            'guest_name' =>
                $user
                    ? null
                    : $validated['guest_name'],

            'reply_to_id' =>
                $validated['reply_to_id']
                ?? null,

            'type' =>
                $validated['type']
                ?? 'text',

            'body' =>
                trim($validated['body']),
        ]);

        $message->load([
            'user:id,name,avatar_url,role',
            'replyTo.user:id,name,avatar_url,role',
        ]);

        broadcast(
            new MessageSent($message)
        )->toOthers();

        return response()->json([
            'message' =>
                'စာပို့ပြီးပါပြီ။',

            'data' =>
                new MessageResource(
                    $message
                ),
        ], 201);
    }

    public function destroy(
        Request $request,
        Message $message
    ): JsonResponse {
        $user = $request->user();

        $canDelete =
            $user->role === 'admin' ||
            (
                $message->user_id &&
                $message->user_id ===
                $user->id
            );

        if (!$canDelete) {
            return response()->json([
                'message' =>
                    'ဤစာကို ဖျက်ခွင့်မရှိပါ။',
            ], 403);
        }

        $messageId = $message->id;

        $message->delete();

        broadcast(
            new MessageDeleted(
                $messageId
            )
        )->toOthers();

        return response()->json([
            'message' =>
                'စာကို ဖျက်ပြီးပါပြီ။',
        ]);
    }
}
