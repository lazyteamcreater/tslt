<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => $validated['password'],

            'role' => 'user',

            'is_active' => true,

            /*
             * Voice Room မဝင်နိုင်သေး။
             * Admin approve လုပ်ပြီးမှ ဝင်နိုင်မယ်။
             */
            'is_approved' => false,
        ]);

        $token = $user
            ->createToken('mobile-app')
            ->plainTextToken;

        return response()->json([
            'message' =>
                'အကောင့်ဖွင့်ပြီးပါပြီ။ Voice Room အသုံးပြုရန် Admin အတည်ပြုချက် လိုအပ်ပါသည်။',

            'data' => [
                'user' => $this->userData($user),

                'token' => $token,

                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(
        LoginRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $user = User::query()
            ->where(
                'email',
                strtolower($validated['email'])
            )
            ->first();

        if (
            !$user ||
            !Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            return response()->json([
                'message' =>
                    'အီးမေးလ် သို့မဟုတ် စကားဝှက် မှားနေပါသည်။',
            ], 422);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' =>
                    'ဤအကောင့်ကို အသုံးပြုခွင့် ပိတ်ထားပါသည်။',
            ], 403);
        }

        $deviceName =
            $validated['device_name']
            ?? 'mobile-app';

        $token = $user
            ->createToken($deviceName)
            ->plainTextToken;

        return response()->json([
            'message' =>
                'အကောင့်ဝင်ရောက်ပြီးပါပြီ။',

            'data' => [
                'user' => $this->userData($user),

                'token' => $token,

                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function me(
        Request $request
    ): JsonResponse {
        return response()->json([
            'data' => [
                'user' =>
                    $this->userData(
                        $request->user()
                    ),
            ],
        ]);
    }

    public function logout(
        Request $request
    ): JsonResponse {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' =>
                'အကောင့်မှ ထွက်ပြီးပါပြီ။',
        ]);
    }

    private function userData(
        User $user
    ): array {
        return [
            'id' => $user->id,

            'name' => $user->name,

            'email' => $user->email,

            'avatar_url' =>
                $user->avatar_url,

            'phone' =>
                $user->phone,

            'gender' =>
                $user->gender,

            'role' =>
                $user->role,

            'is_active' =>
                (bool) $user->is_active,

            'is_approved' =>
                (bool) $user->is_approved,

            /*
             * Flutter UI မှာတိုက်ရိုက်သုံးလို့ရအောင်
             * derived permissions ထည့်ထားမယ်။
             */
            'permissions' => [
                'can_join_voice' =>
                    $user->is_active &&
                    $user->is_approved,

                'can_start_voice' =>
                    $user->is_active &&
                    $user->is_approved &&
                    in_array(
                        $user->role,
                        ['admin', 'streamer'],
                        true
                    ),

                'can_manage_voice' =>
                    $user->is_active &&
                    $user->role === 'admin',

                'can_manage_admin_app' =>
                    $user->is_active &&
                    $user->role === 'admin',
            ],

            'created_at' =>
                $user->created_at
                    ?->toISOString(),
        ];
    }
}
