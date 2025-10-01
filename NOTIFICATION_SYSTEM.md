# Laravel-Style Database Notifications in Symfony

This prototype implements a complete notification system similar to Laravel's database notifications, using Symfony 7.3, Doctrine ORM, and Symfony Messenger.

## Features Implemented

✅ **Notification Entity** with fields: id, user, title, body, url, createdAt, readAt
✅ **Repository method** to fetch unread notifications
✅ **Messenger integration** for async/sync notification processing
✅ **Error handling** with recoverable and unrecoverable exceptions
✅ **REST API endpoints** for creating and managing notifications
✅ **Service wrapper** for easy notification dispatching

## File Structure

```
src/
├── Entity/
│   ├── User.php                    # User entity
│   └── Notification.php            # Notification entity with markAsRead() & isRead()
├── Repository/
│   ├── UserRepository.php
│   └── NotificationRepository.php  # findUnreadForUser() method
├── Message/
│   └── NewNotificationMessage.php  # Messenger message class
├── MessageHandler/
│   └── NewNotificationHandler.php  # Handles notification creation
├── Service/
│   └── Notifier.php               # Convenience service for dispatching
└── Controller/
    └── NotificationController.php  # API endpoints

config/packages/messenger.yaml      # Updated with routing config
```

## Usage

### 1. Send a Notification (Programmatically)

```php
use App\Service\Notifier;

class YourController extends AbstractController
{
    public function someAction(Notifier $notifier): Response
    {
        // Simple usage
        $notifier->notifyUser(
            userId: 1,
            title: 'New Order',
            body: 'You have a new order #12345',
            url: '/orders/12345'
        );

        return $this->json(['status' => 'notification sent']);
    }
}
```

### 2. API Endpoints

**Create a demo notification:**
```bash
curl -X POST http://localhost:8000/notifications/demo
```

**List unread notifications:**
```bash
curl http://localhost:8000/notifications
```

**Mark notification as read:**
```bash
curl -X POST http://localhost:8000/notifications/1/read
```

### 3. Async vs Sync Processing

The system is currently configured for **async** processing (requires running messenger:consume).

**For async (current configuration):**
```bash
# Run this in a separate terminal
php bin/console messenger:consume async
```

**For sync (immediate) processing:**

Edit `config/packages/messenger.yaml` and change:
```yaml
routing:
    # App\Message\NewNotificationMessage: async  # Comment this
    App\Message\NewNotificationMessage: sync     # Uncomment this
```

### 4. Error Handling

The handler implements proper error handling:

- **UnrecoverableMessageHandlingException**: Thrown when user doesn't exist (won't retry)
- **RecoverableMessageHandlingException**: Thrown for transient errors like DB connection issues (will retry)

Retry strategy is configured in `messenger.yaml`:
- Max retries: 3
- Multiplier: 2 (exponential backoff)

### 5. View Failed Messages

```bash
# List failed messages
php bin/console messenger:failed:show

# Retry failed messages
php bin/console messenger:failed:retry
```

## Testing the System

1. **Start the application:**
   ```bash
   symfony serve
   # or
   php -S localhost:8000 -t public/
   ```

2. **Create a test notification:**
   ```bash
   curl -X POST http://localhost:8000/notifications/demo
   ```

3. **If using async, start the worker:**
   ```bash
   php bin/console messenger:consume async -vv
   ```

4. **Fetch unread notifications:**
   ```bash
   curl http://localhost:8000/notifications
   ```

5. **Mark one as read:**
   ```bash
   curl -X POST http://localhost:8000/notifications/1/read
   ```

## Database Schema

The migration created two tables:

**users:**
- id (PK)
- email (unique)
- name

**notifications:**
- id (PK)
- user_id (FK to users)
- title
- body
- url (nullable)
- created_at
- read_at (nullable)

## Next Steps for Production

1. **Authentication**: Replace demo user logic with `$this->getUser()` in controller
2. **Security**: Add proper access control to ensure users can only access their notifications
3. **Pagination**: Add pagination to the list endpoint
4. **Real-time**: Integrate with Mercure or Turbo Streams for live updates
5. **Notification Types**: Create different notification classes for different types
6. **Email/SMS**: Add additional handlers to send email/SMS for important notifications
7. **Preferences**: Allow users to set notification preferences
8. **Batching**: Add support for batching notifications

## Advanced Usage Example

```php
// In any service or controller
class OrderService
{
    public function __construct(
        private Notifier $notifier
    ) {}

    public function completeOrder(Order $order): void
    {
        // ... order completion logic ...

        // Notify the user
        $this->notifier->notifyUser(
            userId: $order->getUser()->getId(),
            title: 'Order Completed',
            body: sprintf('Your order #%s has been completed!', $order->getId()),
            url: sprintf('/orders/%s', $order->getId())
        );
    }
}
```

## Configuration Details

### Messenger Transport (messenger.yaml)

```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    multiplier: 2

        routing:
            App\Message\NewNotificationMessage: async
```

Currently using Doctrine transport (stores messages in database). Can be switched to Redis, RabbitMQ, etc. by changing `MESSENGER_TRANSPORT_DSN` in `.env`.
