<?php

namespace App\Service;

use App\Message\NewNotificationMessage;
use Symfony\Component\Messenger\MessageBusInterface;

class Notifier
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function notifyUser(int $userId, string $title, string $body, ?string $url = null): void
    {
        $this->messageBus->dispatch(
            new NewNotificationMessage($userId, $title, $body, $url)
        );
    }
}
