<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceTokenController extends Controller
{
    public function store(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'max:512',
            ],

            'platform' => [
                'required',

                Rule::in([
                    'android',
                    'ios',
                    'web',
                ]),
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        /*
         * Guest လည်းရ၊ Login user လည်းရ။
         *
         * Authorization Bearer Token ပါလာရင်
         * User account နဲ့ device token ကို link လုပ်မယ်။
         */
        $user = $request->user('sanctum');

        $deviceToken = DeviceToken::query()
            ->where(
                'token',
                $validated['token']
            )
            ->first();

        if (!$deviceToken) {
            $deviceToken =
                new DeviceToken();

            $deviceToken->token =
                $validated['token'];
        }

        /*
         * Login user ဖြစ်မှ user_id update မယ်။
         *
         * Guest request ဖြစ်တာနဲ့
         * အရင် link လုပ်ထားတဲ့ user_id ကို
         * null မလုပ်ဘူး။
         */
        if ($user) {
            $deviceToken->user_id =
                $user->id;
        }

        $deviceToken->platform =
            $validated['platform'];

        $deviceToken->device_name =
            $validated['device_name']
            ?? null;

        $deviceToken->is_active =
            true;

        $deviceToken->last_used_at =
            now();

        $deviceToken->save();

        return response()->json([
            'message' =>
                'Notification device ကို မှတ်တမ်းတင်ပြီးပါပြီ။',

            'data' => [
                'id' =>
                    $deviceToken->id,

                'platform' =>
                    $deviceToken->platform,

                'is_active' =>
                    (bool) $deviceToken->is_active,

                'is_linked_to_account' =>
                    $deviceToken->user_id !== null,
            ],
        ]);
    }

    public function destroy(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'token' => [
                'required',
                'string',
                'max:512',
            ],
        ]);

        DeviceToken::query()
            ->where(
                'token',
                $validated['token']
            )
            ->update([
                'is_active' =>
                    false,

                'last_used_at' =>
                    now(),
            ]);

        return response()->json([
            'message' =>
                'Notification device ကို ပိတ်ပြီးပါပြီ။',
        ]);
    }
}
