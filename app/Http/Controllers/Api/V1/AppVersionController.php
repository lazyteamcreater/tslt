<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function check(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'platform' => [
                'required',
                'in:android,ios',
            ],

            'version' => [
                'required',
                'string',
                'max:30',
            ],

            'build' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $appVersion = AppVersion::query()
            ->where(
                'platform',
                $validated['platform']
            )
            ->where('is_active', true)
            ->first();

        if (!$appVersion) {
            return response()->json([
                'data' => [
                    'update_available' => false,
                    'force_update' => false,
                ],
            ]);
        }

        $currentBuild = (int) $validated['build'];

        $updateAvailable =
            $currentBuild < $appVersion->latest_build;

        $belowMinimum =
            $appVersion->minimum_build !== null &&
            $currentBuild < $appVersion->minimum_build;

        $forceUpdate =
            $updateAvailable &&
            (
                $appVersion->force_update ||
                $belowMinimum
            );

        return response()->json([
            'data' => [
                'update_available' => $updateAvailable,

                'force_update' => $forceUpdate,

                'latest_version' =>
                    $appVersion->latest_version,

                'latest_build' =>
                    $appVersion->latest_build,

                'download_url' =>
                    $appVersion->download_url,

                'message' =>
                    $appVersion->message,
            ],
        ]);
    }
}
