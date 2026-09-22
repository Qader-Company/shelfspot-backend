<?php

namespace App\Modules\Shared\Application\Firebase;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected Messaging $messaging;

    public function __construct()
    {
        $credentialsPath = base_path(
            config('services.firebase.credentials')
        );

        $factory = (new Factory)
            ->withServiceAccount($credentialsPath);

        $this->messaging = $factory->createMessaging();
    }

    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ) {
        $message = CloudMessage::new()
            ->toToken($token)
            ->withNotification(
                Notification::create($title, $body)
            )
            ->withData($data);

        return $this->messaging->send($message);
    }

    public function messaging(): Messaging
    {
        return $this->messaging;
    }
}

