<?php

namespace App\MessageHandler;

use App\Entity\Notification;
use App\Message\NewNotificationMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class NewNotificationHandler
{
    /**
     * Message handler for creating new notifications.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(NewNotificationMessage $message): void
    {
        try {
            $user = $this->userRepository->find($message->getUserId());

            if (!$user) {
                throw new UnrecoverableMessageHandlingException(
                    sprintf('User with ID %d does not exist', $message->getUserId())
                );
            }

            $notification = new Notification();
            $notification->setUser($user);
            $notification->setTitle($message->getTitle());
            $notification->setBody($message->getBody());
            $notification->setUrl($message->getUrl());

            $this->entityManager->persist($notification);
            $this->entityManager->flush();

        } catch (UnrecoverableMessageHandlingException $e) {
            throw $e;
        } catch (\Exception $e) {
            // Transient errors (database connection issues, etc.)
            throw new RecoverableMessageHandlingException(
                sprintf('Failed to create notification: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }
}
