<?php

namespace App\Message;

/**
 * Message class for creating a new notification.
 */
class NewNotificationMessage
{
    public function __construct(
        private readonly int $userId,
        private readonly string $title,
        private readonly string $body,
        private readonly ?string $url = null
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
