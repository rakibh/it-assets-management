<?php

declare(strict_types=1);

namespace Modules\Notifications;

use Core\Session;
use Exception;

class NotificationController
{
    private NotificationRepository $notificationRepository;

    public function __construct()
    {
        $this->notificationRepository = new NotificationRepository();
    }

    /**
     * Get real-time status for polling.
     */
    public function poll(): array
    {
        $userId = (int)Session::get('user_id');
        if (!$userId) return ['success' => false];

        return [
            'success' => true,
            'unread_count' => $this->notificationRepository->getUnreadCount($userId),
            'recent' => $this->notificationRepository->getRecent($userId, 15)
        ];
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(int $id): array
    {
        $userId = (int)Session::get('user_id');
        if (!$userId) return ['success' => false];

        try {
            $this->notificationRepository->markAsRead($userId, $id);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllRead(): array
    {
        $userId = (int)Session::get('user_id');
        if (!$userId) return ['success' => false];

        try {
            $this->notificationRepository->markAllAsRead($userId);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Acknowledge (archive) a notification.
     */
    public function acknowledge(int $id): array
    {
        $userId = (int)Session::get('user_id');
        if (!$userId) return ['success' => false];

        try {
            $this->notificationRepository->acknowledge($userId, $id);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Bulk mark notifications (AJAX).
     */
    public function bulkAction(array $data): array
    {
        $userId = (int)Session::get('user_id');
        if (!$userId) return ['success' => false];

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        $ids = $data['ids'] ?? [];
        $action = $data['action'] ?? '';

        if (empty($ids)) return ['success' => false, 'message' => 'No notifications selected.'];

        try {
            if ($action === 'mark_read') {
                $this->notificationRepository->bulkMarkRead($userId, $ids, true);
            } elseif ($action === 'mark_unread') {
                $this->notificationRepository->bulkMarkRead($userId, $ids, false);
            } elseif ($action === 'archive') {
                foreach ($ids as $id) {
                    $this->notificationRepository->acknowledge($userId, (int)$id);
                }
            }
            return ['success' => true, 'message' => 'Action completed successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Full page view of all notifications.
     */
    public function list(): array
    {
        $userId = (int)Session::get('user_id');
        if (!$userId) throw new Exception("Unauthorized");

        $page = (int)($_GET['page'] ?? 1);
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? ''
        ];

        $settingsRepo = new \Modules\Admin\SettingsRepository();
        $limit = (int)$settingsRepo->get('records_per_page', 20);

        $res = $this->notificationRepository->getAllPaginated($userId, $page, $limit, $filters);

        return [
            'title' => 'Notifications',
            'view' => 'views/notifications/list.php',
            'data' => [
                'notifications' => $res['items'],
                'total' => $res['total'],
                'pages' => $res['pages'],
                'currentPage' => $page,
                'filters' => $filters
            ]
        ];
    }
}
