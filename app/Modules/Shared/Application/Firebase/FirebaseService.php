<?php

namespace App\Modules\Shared\Application\Firebase;

use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification;
use RuntimeException;

class FirebaseService
{
    protected Messaging $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount($this->credentials());

        $this->messaging = $factory->createMessaging();
    }

    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): array {
        $message = $this->message($title, $body, $data);

        return $this->messaging->send($message->toToken($token));
    }

    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
        string $priority = 'normal',
    ): MulticastSendReport {
        $tokens = array_values(array_unique(array_filter(
            $tokens,
            static fn (mixed $token): bool => is_string($token) && $token !== '',
        )));

        if ($tokens === []) {
            throw new RuntimeException('At least one Firebase device token is required.');
        }

        return $this->messaging->sendMulticast(
            $this->message($title, $body, $data, $priority),
            $tokens,
        );
    }

    private function message(
        string $title,
        string $body,
        array $data,
        string $priority = 'normal',
    ): CloudMessage {
        $androidNotification = ['sound' => 'default'];
        $androidChannelId = config('notifications.firebase.android_channel_id');

        if (is_string($androidChannelId) && $androidChannelId !== '') {
            $androidNotification['channel_id'] = $androidChannelId;
        }

        $androidConfig = AndroidConfig::fromArray([
            'priority' => $priority === 'high' ? 'high' : 'normal',
            'notification' => $androidNotification,
        ]);

        $apnsConfig = ApnsConfig::fromArray([
            'headers' => [
                'apns-priority' => '10',
                'apns-push-type' => 'alert',
            ],
            'payload' => [
                'aps' => ['sound' => 'default'],
            ],
        ]);

        return CloudMessage::new()
            ->withNotification(
                Notification::create($title, $body)
            )
            ->withData($data)
            ->withAndroidConfig($androidConfig)
            ->withApnsConfig($apnsConfig);
    }

    public function messaging(): Messaging
    {
        return $this->messaging;
    }

    private function credentials(): string|array
    {
        $encodedCredentials = config('services.firebase.credentials_base64');

        if (is_string($encodedCredentials) && trim($encodedCredentials) !== '') {
            $decodedCredentials = base64_decode($encodedCredentials, true);

            if ($decodedCredentials === false) {
                throw new RuntimeException('FIREBASE_CREDENTIALS_BASE64 is not valid Base64.');
            }

            $credentials = json_decode($decodedCredentials, true);

            if (! is_array($credentials)) {
                throw new RuntimeException('FIREBASE_CREDENTIALS_BASE64 does not contain a valid service-account JSON object.');
            }

            return $credentials;
        }

        $configuredPath = config('services.firebase.credentials');

        if (! is_string($configuredPath) || trim($configuredPath) === '') {
            throw new RuntimeException('Firebase credentials are not configured. Set FIREBASE_CREDENTIALS or FIREBASE_CREDENTIALS_BASE64.');
        }

        $isAbsolute = str_starts_with($configuredPath, '/')
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $configuredPath) === 1;

        $credentialsPath = $isAbsolute ? $configuredPath : base_path($configuredPath);

        if (! is_file($credentialsPath) || ! is_readable($credentialsPath)) {
            throw new RuntimeException("Firebase credentials file is not readable at [{$credentialsPath}].");
        }

        return $credentialsPath;
    }
}
