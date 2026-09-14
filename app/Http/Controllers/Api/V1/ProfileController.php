<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Profile\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(
        Request $request
    ): JsonResponse {
        return response()->json([
            'data' => [
                'user' => $this->userData(
                    $request->user()
                ),
            ],
        ]);
    }

    public function update(
        UpdateProfileRequest $request
    ): JsonResponse {
        $user = $request->user();

        $validated = $request->validated();

        $avatarUrl = $user->avatar_url;

        if ($request->hasFile('avatar')) {
            $oldAvatarUrl = $user->avatar_url;

            $path = $request->file('avatar')->store(
                'profile-avatars',
                'public'
            );

            $avatarUrl = URL::to(
                '/storage/' . ltrim($path, '/')
            );

            if (
                $oldAvatarUrl &&
                Str::contains(
                    $oldAvatarUrl,
                    '/storage/profile-avatars/'
                )
            ) {
                $oldPath = Str::after(
                    $oldAvatarUrl,
                    '/storage/'
                );

                Storage::disk('public')->delete(
                    $oldPath
                );
            }
        }

        $user->update([
            'name' =>
                $validated['name'],

            'email' =>
                strtolower(
                    $validated['email']
                ),

            'avatar_url' =>
                $avatarUrl,

            'gender' =>
                $validated['gender']
                ?? null,
        ]);

        return response()->json([
            'message' =>
                'Profile ကို ပြင်ဆင်ပြီးပါပြီ။',

            'data' => [
                'user' =>
                    $this->userData(
                        $user->fresh()
                    ),
            ],
        ]);
    }

    public function changePassword(
        ChangePasswordRequest $request
    ): JsonResponse {
        $user = $request->user();

        $validated =
            $request->validated();

        if (
            !Hash::check(
                $validated['current_password'],
                $user->password
            )
        ) {
            return response()->json([
                'message' =>
                    'လက်ရှိစကားဝှက် မှားနေပါသည်။',
            ], 422);
        }

        $user->update([
            'password' =>
                $validated['password'],
        ]);

        /*
         * Password ပြောင်းပြီးရင်
         * Login token အားလုံး revoke လုပ်မယ်။
         */
        $user->tokens()->delete();

        return response()->json([
            'message' =>
                'စကားဝှက်ကို ပြောင်းပြီးပါပြီ။ ကျေးဇူးပြု၍ ပြန်လည်အကောင့်ဝင်ပါ။',

            'data' => [
                'requires_login' => true,
            ],
        ]);
    }

    private function userData(
        User $user
    ): array {
        return [
            'id' =>
                $user->id,

            'name' =>
                $user->name,

            'email' =>
                $user->email,

            'avatar_url' =>
                $user->avatar_url,

            'phone' =>
                $user->phone,

            'gender' =>
                $user->gender,

            'role' =>
                $user->role,

            'is_active' =>
                $user->is_active,

            'is_approved' =>
                $user->is_approved,

            'permissions' => [
                'can_join_voice' =>
                    $user->canJoinVoice(),

                'can_start_voice' =>
                    $user->canStartVoice(),

                'can_manage_voice' =>
                    $user->canManageVoice(),

                'can_manage_admin_app' =>
                    $user->is_active &&
                    $user->isAdmin(),
            ],

            'created_at' =>
                $user->created_at
                    ?->toISOString(),

            'updated_at' =>
                $user->updated_at
                    ?->toISOString(),
        ];
    }
}
