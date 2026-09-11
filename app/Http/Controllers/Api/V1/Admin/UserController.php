<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Admin App - User List
     */
    public function index(
        Request $request
    ): JsonResponse {
        $query = User::query()
            ->latest('id');

        /*
         * Search
         */
        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'phone',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
         * Approval filter
         *
         * ?approval=pending
         * ?approval=approved
         */
        if (
            $request->input('approval') ===
            'pending'
        ) {
            $query->where(
                'is_approved',
                false
            );
        }

        if (
            $request->input('approval') ===
            'approved'
        ) {
            $query->where(
                'is_approved',
                true
            );
        }

        /*
         * Role filter
         *
         * ?role=user
         * ?role=streamer
         * ?role=admin
         */
        if (
            in_array(
                $request->input('role'),
                [
                    'user',
                    'streamer',
                    'admin',
                ],
                true
            )
        ) {
            $query->where(
                'role',
                $request->input('role')
            );
        }

        /*
         * Active status
         *
         * ?status=active
         * ?status=blocked
         */
        if (
            $request->input('status') ===
            'active'
        ) {
            $query->where(
                'is_active',
                true
            );
        }

        if (
            $request->input('status') ===
            'blocked'
        ) {
            $query->where(
                'is_active',
                false
            );
        }

        $users = $query
            ->paginate(30);

        return response()->json([
            'data' => collect(
                $users->items()
            )
                ->map(
                    fn (User $user) =>
                    $this->userData($user)
                )
                ->values(),

            'meta' => [
                'current_page' =>
                    $users->currentPage(),

                'last_page' =>
                    $users->lastPage(),

                'per_page' =>
                    $users->perPage(),

                'total' =>
                    $users->total(),
            ],
        ]);
    }

    /**
     * Admin App - User Detail
     */
    public function show(
        User $user
    ): JsonResponse {
        return response()->json([
            'data' => [
                'user' =>
                    $this->userData($user),
            ],
        ]);
    }

    /**
     * Approve user for Voice Room.
     */
    public function approve(
        User $user
    ): JsonResponse {
        $user->update([
            'is_approved' => true,
            'is_active' => true,
        ]);

        return response()->json([
            'message' =>
                'အကောင့်ကို အတည်ပြုပြီးပါပြီ။',

            'data' => [
                'user' =>
                    $this->userData(
                        $user->fresh()
                    ),
            ],
        ]);
    }

    /**
     * Revoke Voice Room approval.
     */
    public function revokeApproval(
        User $user
    ): JsonResponse {
        /*
         * Admin approval ကိုဒီ endpoint ကနေ
         * မဖြုတ်စေချင်ဘူး။
         */
        if ($user->role === 'admin') {
            return response()->json([
                'message' =>
                    'Admin account ၏ Approval ကို ဖြုတ်၍မရပါ။',
            ], 422);
        }

        $user->update([
            'is_approved' => false,
        ]);

        return response()->json([
            'message' =>
                'အကောင့်အတည်ပြုချက်ကို ပြန်လည်ရုပ်သိမ်းပြီးပါပြီ။',

            'data' => [
                'user' =>
                    $this->userData(
                        $user->fresh()
                    ),
            ],
        ]);
    }

    /**
     * Block / Activate user.
     *
     * JSON:
     * {
     *   "is_active": false
     * }
     */
    public function updateStatus(
        Request $request,
        User $user
    ): JsonResponse {
        $validated =
            $request->validate([
                'is_active' => [
                    'required',
                    'boolean',
                ],
            ]);

        /*
         * Current admin ကို current admin ကိုယ်တိုင်
         * block မလုပ်စေဘူး။
         */
        if (
            $request->user()->id ===
                $user->id &&
            !$validated['is_active']
        ) {
            return response()->json([
                'message' =>
                    'မိမိ Admin account ကို မိမိကိုယ်တိုင် ပိတ်၍မရပါ။',
            ], 422);
        }

        $user->update([
            'is_active' =>
                $validated['is_active'],
        ]);

        /*
         * Block လုပ်လိုက်ရင်
         * existing login tokens revoke.
         */
        if (!$validated['is_active']) {
            $user->tokens()->delete();

            /*
             * Firebase notifications မပို့တော့ရန်။
             */
        }

        return response()->json([
            'message' =>
                $validated['is_active']
                    ? 'အကောင့်ကို ပြန်လည်ဖွင့်ပေးပြီးပါပြီ။'
                    : 'အကောင့်ကို ပိတ်ထားပြီးပါပြီ။',

            'data' => [
                'user' =>
                    $this->userData(
                        $user->fresh()
                    ),
            ],
        ]);
    }

    /**
     * Change Role.
     *
     * user
     * streamer
     * admin
     */
    public function updateRole(
        Request $request,
        User $user
    ): JsonResponse {
        $validated =
            $request->validate([
                'role' => [
                    'required',

                    Rule::in([
                        'user',
                        'streamer',
                        'admin',
                    ]),
                ],
            ]);

        /*
         * Current logged-in Admin က
         * ကိုယ့် role ကို user/streamer ပြောင်းမချနိုင်။
         */
        if (
            $request->user()->id ===
                $user->id &&
            $validated['role'] !== 'admin'
        ) {
            return response()->json([
                'message' =>
                    'မိမိ Admin role ကို မိမိကိုယ်တိုင် ပြောင်း၍မရပါ။',
            ], 422);
        }

        $updates = [
            'role' =>
                $validated['role'],
        ];

        /*
         * Streamer/Admin ဖြစ်သွားရင်
         * Voice approval အလိုအလျောက်ပေးမယ်။
         */
        if (
            $validated['role'] === 'streamer' ||
            $validated['role'] === 'admin'
        ) {
            $updates['is_approved'] = true;
            $updates['is_active'] = true;
        }

        $user->update($updates);

        return response()->json([
            'message' =>
                'Account role ကို ပြောင်းပြီးပါပြီ။',

            'data' => [
                'user' =>
                    $this->userData(
                        $user->fresh()
                    ),
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
                (bool) $user->is_active,

            'is_approved' =>
                (bool) $user->is_approved,

            'permissions' => [
                'can_join_voice' =>
                    $user->is_active &&
                    $user->is_approved,

                'can_start_voice' =>
                    $user->is_active &&
                    $user->is_approved &&
                    in_array(
                        $user->role,
                        [
                            'admin',
                            'streamer',
                        ],
                        true
                    ),

                'can_manage_voice' =>
                    $user->is_active &&
                    $user->role === 'admin',
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
