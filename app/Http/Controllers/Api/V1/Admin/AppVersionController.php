<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateAppVersionRequest;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;

class AppVersionController extends Controller
{
    public function index(): JsonResponse
    {
        $versions = AppVersion::query()
            ->orderBy('platform')
            ->get();

        return response()->json([
            'data' => [
                'versions' => $versions,
            ],
        ]);
    }

    public function update(
        UpdateAppVersionRequest $request
    ): JsonResponse {
        $validated = $request->validated();

        $appVersion = AppVersion::updateOrCreate(
            [
                'platform' => $validated['platform'],
            ],
            [
                'latest_version' =>
                    $validated['latest_version'],

                'latest_build' =>
                    $validated['latest_build'],

                'minimum_version' =>
                    $validated['minimum_version'] ?? null,

                'minimum_build' =>
                    $validated['minimum_build'] ?? null,

                'force_update' =>
                    $validated['force_update'] ?? false,

                'download_url' =>
                    $validated['download_url'] ?? null,

                'message' =>
                    $validated['message'] ?? null,

                'is_active' =>
                    $validated['is_active'] ?? true,
            ]
        );

        return response()->json([
            'message' => 'App version ပြင်ဆင်ပြီးပါပြီ။',

            'data' => [
                'version' => $appVersion,
            ],
        ]);
    }
}
