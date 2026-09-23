<?php

namespace App\Notifications\Channels;

use App\Modules\Shared\Application\Firebase\FirebaseService;
use App\Modules\V1\Users\Domain\Models\DeviceToken;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class FirebaseChannel
{
    public function __construct(private readonly FirebaseService $firebase) {}

    public function send(object $notifiable, Notification $notification): ?array
    {
        if (! config('notifications.firebase.enabled')
            || ! $notifiable instanceof User
            || $notifiable->type !== PortalTypeEnum::WORKER
            || ! method_exists($notification, 'toFirebase')
            || (method_exists($notification, 'shouldSendToFirebase')
                && ! $notification->shouldSendToFirebase($notifiable))) {
            return null;
        }

        $notifiable->loadMissing('deviceTokens');

        $tokens = $notifiable->deviceTokens
            ->pluck('token')
            ->filter(fn (mixed $token): bool => is_string($token) && $token !== '')
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return [
                'attempted' => 0,
                'successful' => 0,
                'failed' => 0,
                'pruned' => 0,
            ];
        }

        $deliveryKeys = $this->reserveDeliveries($notifiable, $notification, $tokens);

        if ($deliveryKeys === []) {
            return null;
        }

        $tokens = array_keys($deliveryKeys);

        try {
            $message = $notification->toFirebase($notifiable);
            $report = $this->firebase->sendToTokens(
                tokens: $tokens,
                title: $message['title'],
                body: $message['body'],
                data: $message['data'],
                priority: $message['priority'],
            );
        } catch (Throwable $exception) {
            $this->releaseDeliveries(array_values($deliveryKeys));

            throw $exception;
        }

        $expiredTokens = array_values(array_unique([
            ...$report->unknownTokens(),
            ...$report->invalidTokens(),
        ]));

        $pruned = $expiredTokens === []
            ? 0
            : DeviceToken::query()->whereIn('token', $expiredTokens)->delete();

        $retryableTokens = collect($report->failures()->getItems())
            ->reject(fn ($item): bool => $item->messageWasInvalid()
                || $item->messageTargetWasInvalid()
                || $item->messageWasSentToUnknownToken())
            ->map(fn ($item): string => $item->target()->value())
            ->values()
            ->all();

        $this->releaseDeliveries(array_values(array_intersect_key(
            $deliveryKeys,
            array_flip($retryableTokens),
        )));

        if ($report->hasFailures()) {
            Log::warning('Firebase notification delivery partially failed.', [
                'user_id' => $notifiable->getKey(),
                'notification_id' => $notification->id,
                'attempted' => count($tokens),
                'failed' => $report->failures()->count(),
                'pruned' => $pruned,
                'error_types' => collect($report->failures()->getItems())
                    ->map(fn ($item) => $item->error() ? $item->error()::class : null)
                    ->filter()
                    ->unique()
                    ->take(5)
                    ->values()
                    ->all(),
            ]);
        }

        if ($retryableTokens !== []) {
            throw new RuntimeException(
                sprintf('Firebase delivery failed for %d worker device(s).', count($retryableTokens)),
                previous: $report->failures()->getItems()[0]->error(),
            );
        }

        return [
            'attempted' => count($tokens),
            'successful' => $report->successes()->count(),
            'failed' => $report->failures()->count(),
            'pruned' => $pruned,
        ];
    }

    /** @return array<string, string|null> Token-to-delivery-key map. */
    private function reserveDeliveries(User $notifiable, Notification $notification, array $tokens): array
    {
        if (! method_exists($notification, 'dedupeKey')) {
            return array_fill_keys($tokens, null);
        }

        $dedupeKey = $notification->dedupeKey($notifiable);

        if ($dedupeKey === null) {
            return array_fill_keys($tokens, null);
        }

        $now = now();
        $reserved = [];

        foreach ($tokens as $token) {
            $deviceDeliveryKey = $dedupeKey.':device:'.hash('sha256', $token);
            $inserted = DB::table('notification_deliveries')->insertOrIgnore([
                'dedupe_key' => $deviceDeliveryKey,
                'channel' => 'firebase',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if ($inserted === 1) {
                $reserved[$token] = $deviceDeliveryKey;
            }
        }

        return $reserved;
    }

    private function releaseDeliveries(array $deliveryKeys): void
    {
        $deliveryKeys = array_values(array_filter($deliveryKeys, 'is_string'));

        if ($deliveryKeys === []) {
            return;
        }

        DB::table('notification_deliveries')
            ->whereIn('dedupe_key', $deliveryKeys)
            ->where('channel', 'firebase')
            ->delete();
    }
}
