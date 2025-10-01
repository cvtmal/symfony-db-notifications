<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\Notifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/demo', name: 'notification_demo', methods: ['POST'])]
    public function demo(Request $request, Notifier $notifier, EntityManagerInterface $em): JsonResponse
    {
        // For demo purposes, we'll use the first user or create one
        $userRepo = $em->getRepository(User::class);
        $user = $userRepo->findOneBy([]);

        if (!$user) {
            $user = new User();
            $user->setEmail('demo@example.com');
            $user->setName('Demo User');
            $em->persist($user);
            $em->flush();
        }

        $notifier->notifyUser(
            $user->getId(),
            'Test Notification',
            'This is a test notification from the demo endpoint.',
            '/dashboard'
        );

        return $this->json([
            'message' => 'Notification queued successfully',
            'user_id' => $user->getId(),
        ]);
    }

    #[Route('', name: 'notification_list', methods: ['GET'])]
    public function list(NotificationRepository $notificationRepo, EntityManagerInterface $em): JsonResponse
    {
        // For demo purposes, get the first user (in production, use getUser() for logged-in user)
        $userRepo = $em->getRepository(User::class);
        $user = $userRepo->findOneBy([]);

        if (!$user) {
            return $this->json([
                'error' => 'No user found. Please create a user first by calling POST /notifications/demo',
            ], Response::HTTP_NOT_FOUND);
        }

        $notifications = $notificationRepo->findUnreadForUser($user);

        return $this->json([
            'notifications' => array_map(function (Notification $n) {
                return [
                    'id' => $n->getId(),
                    'title' => $n->getTitle(),
                    'body' => $n->getBody(),
                    'url' => $n->getUrl(),
                    'created_at' => $n->getCreatedAt()->format('c'),
                    'is_read' => $n->isRead(),
                ];
            }, $notifications),
        ]);
    }

    #[Route('/{id}/read', name: 'notification_mark_read', methods: ['POST'])]
    public function markAsRead(Notification $notification, EntityManagerInterface $em): JsonResponse
    {
        if ($notification->isRead()) {
            return $this->json([
                'message' => 'Notification already marked as read',
                'notification' => [
                    'id' => $notification->getId(),
                    'read_at' => $notification->getReadAt()?->format('c'),
                ],
            ]);
        }

        $notification->markAsRead();
        $em->flush();

        return $this->json([
            'message' => 'Notification marked as read',
            'notification' => [
                'id' => $notification->getId(),
                'read_at' => $notification->getReadAt()?->format('c'),
            ],
        ]);
    }
}
