<?php

namespace App\Services\Firebase;

use App\Models\DeviceToken;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Throwable;

class FirebaseNotificationService
{
    public function __construct(
        private readonly Messaging $messaging
    ) {
    }

    /**
     * Send notification to all active devices.
     */
    public function sendToAll(
        string $title,
        string $body,
        array $data = []
    ): array {
        $tokens = DeviceToken::query()
            ->where('is_active', true)
            ->pluck('token')
            ->filter()
            ->unique()
            ->values();

        if ($tokens->isEmpty()) {
            return [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
            ];
        }

        $totalSuccess = 0;
        $totalFailed = 0;

        /*
         * Firebase multicast တစ်ကြိမ်မှာ
         * token 500 အထိပို့မယ်။
         */
        foreach ($tokens->chunk(500) as $chunk) {
            $result = $this->sendChunk(
                tokens: $chunk->values()->all(),
                title: $title,
                body: $body,
                data: $data
            );

            $totalSuccess +=
                $result['success'];

            $totalFailed +=
                $result['failed'];
        }

        return [
            'total' =>
                $tokens->count(),

            'success' =>
                $totalSuccess,

            'failed' =>
                $totalFailed,
        ];
    }

    private function sendChunk(
        array $tokens,
        string $title,
        string $body,
        array $data
    ): array {
        /*
         * FCM data payload values ကို
         * string အဖြစ်ပဲပို့မယ်။
         */
        $normalizedData =
            collect($data)
                ->mapWithKeys(
                    function (
                        mixed $value,
                        string $key
                    ): array {
                        if (is_bool($value)) {
                            $value =
                                $value
                                    ? 'true'
                                    : 'false';
                        } elseif (
                            is_array($value) ||
                            is_object($value)
                        ) {
                            $value =
                                json_encode(
                                    $value,
                                    JSON_UNESCAPED_UNICODE
                                );
                        } elseif ($value === null) {
                            $value = '';
                        } else {
                            $value =
                                (string) $value;
                        }

                        return [
                            $key => $value,
                        ];
                    }
                )
                ->all();


        $message = CloudMessage::new()
            ->withNotification(
                Notification::create(
                    $title,
                    $body
                )
            )
            ->withData(
                $normalizedData
            );

        try {
            $report =
                $this->messaging
                    ->sendMulticast(
                        $message,
                        $tokens
                    );

            /*
             * Firebase က invalid / unknown
             * registration tokens ပြန်ပေးလာရင်
             * DB မှာ disable လုပ်မယ်။
             */
            $invalidTokens =
                collect(
                    $report->invalidTokens()
                )
                    ->merge(
                        $report->unknownTokens()
                    )
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

            if (!empty($invalidTokens)) {
                DeviceToken::query()
                    ->whereIn(
                        'token',
                        $invalidTokens
                    )
                    ->update([
                        'is_active' => false,
                    ]);
            }

            return [
                'success' =>
                    $report->successes()
                        ->count(),

                'failed' =>
                    $report->failures()
                        ->count(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'success' => 0,
                'failed' => count($tokens),
            ];
        }
    }
}
