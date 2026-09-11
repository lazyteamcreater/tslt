<?php

use App\Http\Controllers\Api\V1\AppVersionController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\MediaCategoryController;
use App\Http\Controllers\Api\V1\MediaController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\VoiceModerationController;
use App\Http\Controllers\Api\V1\VoiceSessionController;
use App\Http\Controllers\Api\V1\Admin\AppVersionController as AdminAppVersionController;
use App\Http\Controllers\Api\V1\Admin\MediaCategoryController as AdminMediaCategoryController;
use App\Http\Controllers\Api\V1\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Api\V1\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/auth/register',
        [
            AuthController::class,
            'register',
        ]
    )->middleware('throttle:10,1');

    Route::post(
        '/auth/login',
        [
            AuthController::class,
            'login',
        ]
    )->middleware('throttle:10,1');

    /*
    |--------------------------------------------------------------------------
    | Public Media
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/media-categories',
        [
            MediaCategoryController::class,
            'index',
        ]
    );

    Route::get(
        '/media',
        [
            MediaController::class,
            'index',
        ]
    );

    Route::get(
        '/media/{medium}',
        [
            MediaController::class,
            'show',
        ]
    );

    Route::post(
        '/media/{medium}/view',
        [
            MediaController::class,
            'incrementView',
        ]
    )->middleware('throttle:60,1');

    /*
    |--------------------------------------------------------------------------
    | Public App Version
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/app-version/check',
        [
            AppVersionController::class,
            'check',
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | Public Chat
    |--------------------------------------------------------------------------
    |
    | Account မရှိလည်း Chat ရေးနိုင်တယ်။
    |
    */

    Route::get(
        '/messages',
        [
            MessageController::class,
            'index',
        ]
    );

    Route::post(
        '/messages',
        [
            MessageController::class,
            'store',
        ]
    )->middleware('throttle:30,1');

    /*
    |--------------------------------------------------------------------------
    | Firebase Device Token
    |--------------------------------------------------------------------------
    |
    | Guest + Login User နှစ်မျိုးလုံး။
    |
    */

    Route::post(
        '/device-token',
        [
            DeviceTokenController::class,
            'store',
        ]
    )->middleware('throttle:20,1');

    Route::delete(
        '/device-token',
        [
            DeviceTokenController::class,
            'destroy',
        ]
    )->middleware('throttle:20,1');

    /*
    |--------------------------------------------------------------------------
    | Public Voice Status
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/voice/status',
        [
            VoiceSessionController::class,
            'status',
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | Authenticated User APIs
    |--------------------------------------------------------------------------
    */

    Route::middleware(
        'auth:sanctum'
    )->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/auth/me',
            [
                AuthController::class,
                'me',
            ]
        );

        Route::post(
            '/auth/logout',
            [
                AuthController::class,
                'logout',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/profile',
            [
                ProfileController::class,
                'show',
            ]
        );

        Route::put(
            '/profile',
            [
                ProfileController::class,
                'update',
            ]
        );

        Route::put(
            '/profile/password',
            [
                ProfileController::class,
                'changePassword',
            ]
        )->middleware('throttle:5,1');

        /*
        |--------------------------------------------------------------------------
        | Chat
        |--------------------------------------------------------------------------
        |
        | Guest message deletion မလုပ်သေး။
        | Registered owner / Admin ပဲ delete ရမယ်။
        |
        */

        Route::delete(
            '/messages/{message}',
            [
                MessageController::class,
                'destroy',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Voice Room
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/voice/start',
            [
                VoiceSessionController::class,
                'start',
            ]
        );

        Route::post(
            '/voice/join',
            [
                VoiceSessionController::class,
                'join',
            ]
        );

        Route::post(
            '/voice/end',
            [
                VoiceSessionController::class,
                'end',
            ]
        );

        Route::get(
            '/voice/participants',
            [
                VoiceSessionController::class,
                'participants',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Voice Moderation
        |--------------------------------------------------------------------------
        |
        | ဒီ APIs ကို User App ကခေါ်မယ်။
        | Controller က Admin role စစ်မယ်။
        |
        */

        Route::post(
            '/voice/moderation/lock-all',
            [
                VoiceModerationController::class,
                'lockAll',
            ]
        );

        Route::post(
            '/voice/moderation/unlock-all',
            [
                VoiceModerationController::class,
                'unlockAll',
            ]
        );

        Route::post(
            '/voice/moderation/users/{user}/lock',
            [
                VoiceModerationController::class,
                'lockParticipant',
            ]
        );

        Route::post(
            '/voice/moderation/users/{user}/unlock',
            [
                VoiceModerationController::class,
                'unlockParticipant',
            ]
        );

        Route::post(
            '/voice/moderation/users/{user}/mute',
            [
                VoiceModerationController::class,
                'muteParticipant',
            ]
        );

        Route::post(
            '/voice/moderation/users/{user}/kick',
            [
                VoiceModerationController::class,
                'kickParticipant',
            ]
        );
    });

    /*
    |--------------------------------------------------------------------------
    | Admin App APIs
    |--------------------------------------------------------------------------
    */

    Route::middleware([
        'auth:sanctum',
        'admin',
    ])
        ->prefix('admin')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Users
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/users',
                [
                    AdminUserController::class,
                    'index',
                ]
            );

            Route::get(
                '/users/{user}',
                [
                    AdminUserController::class,
                    'show',
                ]
            );

            Route::post(
                '/users/{user}/approve',
                [
                    AdminUserController::class,
                    'approve',
                ]
            );

            Route::post(
                '/users/{user}/revoke-approval',
                [
                    AdminUserController::class,
                    'revokeApproval',
                ]
            );

            Route::put(
                '/users/{user}/status',
                [
                    AdminUserController::class,
                    'updateStatus',
                ]
            );

            Route::put(
                '/users/{user}/role',
                [
                    AdminUserController::class,
                    'updateRole',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Media Categories
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/media-categories',
                [
                    AdminMediaCategoryController::class,
                    'index',
                ]
            );

            Route::post(
                '/media-categories',
                [
                    AdminMediaCategoryController::class,
                    'store',
                ]
            );

            Route::get(
                '/media-categories/{mediaCategory}',
                [
                    AdminMediaCategoryController::class,
                    'show',
                ]
            );

            Route::put(
                '/media-categories/{mediaCategory}',
                [
                    AdminMediaCategoryController::class,
                    'update',
                ]
            );

            Route::delete(
                '/media-categories/{mediaCategory}',
                [
                    AdminMediaCategoryController::class,
                    'destroy',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Media
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/media',
                [
                    AdminMediaController::class,
                    'index',
                ]
            );

            Route::post(
                '/media',
                [
                    AdminMediaController::class,
                    'store',
                ]
            );

            Route::get(
                '/media/{medium}',
                [
                    AdminMediaController::class,
                    'show',
                ]
            );

            Route::put(
                '/media/{medium}',
                [
                    AdminMediaController::class,
                    'update',
                ]
            );

            Route::delete(
                '/media/{medium}',
                [
                    AdminMediaController::class,
                    'destroy',
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Notifications
            |--------------------------------------------------------------------------
            */

            Route::post(
                '/notifications/send',
                [
                    AdminNotificationController::class,
                    'send',
                ]
            )->middleware('throttle:10,1');

            /*
            |--------------------------------------------------------------------------
            | App Versions
            |--------------------------------------------------------------------------
            */

            Route::get(
                '/app-versions',
                [
                    AdminAppVersionController::class,
                    'index',
                ]
            );

            Route::put(
                '/app-versions/{appVersion}',
                [
                    AdminAppVersionController::class,
                    'update',
                ]
            );
        });
});
