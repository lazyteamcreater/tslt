<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Firebase\FirebaseNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly FirebaseNotificationService
            $notificationService
    ) {
    }

    public function send(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'title' => [
                'required',
                'string',
                'max:150',
            ],

            'body' => [
                'required',
                'string',
                'max:1000',
            ],

            'type' => [
                'nullable',
                'string',
                'max:50',
            ],

            'media_id' => [
                'nullable',
                'integer',
            ],

            'url' => [
                'nullable',
                'url',
                'max:2048',
            ],
        ]);

        $data = [];

        if (!empty($validated['type'])) {
            $data['type'] =
                $validated['type'];
        }

        if (!empty($validated['media_id'])) {
            $data['media_id'] =
                (string) $validated['media_id'];
        }

        if (!empty($validated['url'])) {
            $data['url'] =
                $validated['url'];
        }

        $result =
            $this->notificationService
                ->sendToAll(
                    title:
                        $validated['title'],

                    body:
                        $validated['body'],

                    data:
                        $data
                );

        return response()->json([
            'message' =>
                'Notification ပို့ပြီးပါပြီ။',

            'data' => [
                'total_devices' =>
                    $result['total'],

                'success' =>
                    $result['success'],

                'failed' =>
                    $result['failed'],
            ],
        ]);
    }
}
