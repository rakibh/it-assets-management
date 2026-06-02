<?php

declare(strict_types=1);

namespace Modules\Tasks;

use Core\Repository;
use PDO;
use Exception;

class TaskRepository extends Repository
{
    /**
     * Get tasks with advanced filtering and pagination.
     */
    public function getTasks(array $filters = [], int $page = 1, int $limit = 20): array
    {
        // Automation: Check deadlines and trigger notifications
        $this->checkDeadlines();

        $offset = ($page - 1) * $limit;
        $params = [];

        $where = " WHERE 1=1";

        // Tab Logic
        if (!empty($filters['tab'])) {
            if ($filters['tab'] === 'board') {
                $where .= " AND t.status IN ('todo', 'doing', 'past_due')";
            } elseif ($filters['tab'] === 'done') {
                $where .= " AND t.status = 'done'";
            } elseif ($filters['tab'] === 'dropped') {
                $where .= " AND t.status = 'dropped'";
            }
            // 'all' doesn't need status filter
        }

        if (!empty($filters['priority'])) {
            $where .= " AND t.priority = :priority";
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['assignee_id'])) {
            $where .= " AND t.id IN (SELECT task_id FROM task_assignees WHERE user_id = :assignee_id)";
            $params['assignee_id'] = $filters['assignee_id'];
        }

        if (!empty($filters['search'])) {
            $where .= " AND (t.title LIKE :s1 OR t.description LIKE :s2 OR t.tags LIKE :s3)";
            $params['s1'] = '%' . $filters['search'] . '%';
            $params['s2'] = '%' . $filters['search'] . '%';
            $params['s3'] = '%' . $filters['search'] . '%';
        }

        $sortBy = $filters['sort_by'] ?? 'deadline';
        $sortDir = strtoupper($filters['sort_dir'] ?? 'ASC');
        
        $allowedSort = ['deadline', 'created_at', 'priority', 'status'];
        if (!in_array($sortBy, $allowedSort)) $sortBy = 'deadline';
        
        $allowedDir = ['ASC', 'DESC'];
        if (!in_array($sortDir, $allowedDir)) $sortDir = 'ASC';

        $query = "
            SELECT t.*, u.username as creator_name, u.profile_photo as creator_photo,
            GROUP_CONCAT(ua.username SEPARATOR ', ') as assignee_names,
            GROUP_CONCAT(ua.profile_photo SEPARATOR '|||') as assignee_photos,
            (SELECT COUNT(*) FROM task_attachments WHERE task_id = t.id) as attachment_count,
            (SELECT COUNT(*) FROM task_comments WHERE task_id = t.id) as comment_count
            FROM tasks t
            LEFT JOIN users u ON t.creator_id = u.id
            LEFT JOIN task_assignees ta ON t.id = ta.task_id
            LEFT JOIN users ua ON ta.user_id = ua.id
            $where
            GROUP BY t.id 
            ORDER BY $sortBy $sortDir
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        $items = $stmt->fetchAll();

        // Total count
        $countQuery = "SELECT COUNT(*) FROM tasks t $where";
        if (!empty($filters['search']) || !empty($filters['assignee_id'])) {
             // For complex search, we might need a more complex count, but let's try simple first
             $countQuery = "SELECT COUNT(DISTINCT t.id) FROM tasks t LEFT JOIN task_assignees ta ON t.id = ta.task_id LEFT JOIN users ua ON ta.user_id = ua.id $where";
        }

        $countStmt = $this->db->prepare($countQuery);
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        return [
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Check for overdue and approaching deadlines and trigger notifications.
     */
    public function checkDeadlines(): void
    {
        // 1. Handle Overdue (Tasks that just passed their deadline)
        $stmt = $this->db->prepare("
            SELECT id, title, priority, status, deadline 
            FROM tasks 
            WHERE status = 'todo' 
            AND deadline IS NOT NULL 
            AND deadline < NOW()
        ");
        $stmt->execute();
        $overdueTasks = $stmt->fetchAll();

        $notificationRepo = new \Modules\Notifications\NotificationRepository();

        foreach ($overdueTasks as $task) {
            $this->db->prepare("UPDATE tasks SET status = 'past_due' WHERE id = ?")->execute([$task['id']]);
            
            $assigneesStmt = $this->db->prepare("SELECT user_id FROM task_assignees WHERE task_id = ?");
            $assigneesStmt->execute([$task['id']]);
            $assigneeIds = $assigneesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($assigneeIds)) {
                $deadline = new \DateTime($task['deadline']);
                $now = new \DateTime();
                $diff = $now->diff($deadline);
                $hours = ($diff->days * 24) + $diff->h;
                $priority = ucfirst($task['priority']);
                $msg = "Task \"{$task['title']}\" is {$hours}h overdue. Priority: {$priority}";

                $notificationRepo->createForUsers(
                    $assigneeIds,
                    'task_overdue',
                    $msg,
                    'high',
                    ['task_id' => $task['id']]
                );
            }
            $this->logAudit('update_status', 'tasks', (int)$task['id'], ['status' => $task['status']], ['status' => 'past_due']);
        }

        // 2. Handle Approaching (Deadline within next 24 hours)
        $stmt = $this->db->prepare("
            SELECT id, title, priority, deadline 
            FROM tasks 
            WHERE status IN ('todo', 'doing') 
            AND deadline IS NOT NULL 
            AND deadline > NOW() 
            AND deadline <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute();
        $approachingTasks = $stmt->fetchAll();

        foreach ($approachingTasks as $task) {
            // Check if already notified in the last 24 hours to avoid spamming
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE type = 'task_approaching' 
                AND JSON_UNQUOTE(JSON_EXTRACT(data, '$.task_id')) = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $checkStmt->execute([$task['id']]);
            
            if ((int)$checkStmt->fetchColumn() === 0) {
                $assigneesStmt = $this->db->prepare("SELECT user_id FROM task_assignees WHERE task_id = ?");
                $assigneesStmt->execute([$task['id']]);
                $assigneeIds = $assigneesStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($assigneeIds)) {
                    $notificationRepo->createForUsers(
                        $assigneeIds,
                        'task_approaching',
                        "Task deadline approaching (within 24h): " . $task['title'],
                        'medium',
                        ['task_id' => $task['id']]
                    );
                }
            }
        }
    }

    /**
     * Get Status Counts for Board/Tabs.
     */
    public function getStatusCounts(): array
    {
        $stmt = $this->db->query("SELECT status, COUNT(*) as count FROM tasks GROUP BY status");
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'todo' => $results['todo'] ?? 0,
            'doing' => $results['doing'] ?? 0,
            'past_due' => $results['past_due'] ?? 0,
            'done' => $results['done'] ?? 0,
            'dropped' => $results['dropped'] ?? 0,
            'all' => array_sum($results)
        ];
    }

    /**
     * Get a single task by ID with assignees and attachments.
     */
    public function getTaskById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT t.*, u.username as creator_name, u.profile_photo as creator_photo FROM tasks t LEFT JOIN users u ON t.creator_id = u.id WHERE t.id = ?");
        $stmt->execute([$id]);
        $task = $stmt->fetch();

        if (!$task) return null;

        // Get Assignees
        $stmt = $this->db->prepare("SELECT u.id, u.username, u.profile_photo FROM users u JOIN task_assignees ta ON u.id = ta.user_id WHERE ta.task_id = ?");
        $stmt->execute([$id]);
        $task['assignees'] = $stmt->fetchAll();

        // Get Attachments
        $stmt = $this->db->prepare("SELECT * FROM task_attachments WHERE task_id = ?");
        $stmt->execute([$id]);
        $task['attachments'] = $stmt->fetchAll();

        return $task;
    }

    /**
     * Create a new task with assignees.
     */
    public function createTask(array $data, array $assignees): int
    {
        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO tasks (title, description, status, priority, recurrence, recurrence_metadata, deadline, creator_id)
                VALUES (:title, :description, :status, :priority, :recurrence, :recurrence_metadata, :deadline, :creator_id)
            ");
            
            $stmt->execute([
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'] ?? 'todo',
                'priority' => $data['priority'] ?? 'medium',
                'recurrence' => $data['recurrence'] ?? 'none',
                'recurrence_metadata' => $data['recurrence_metadata'] ?? null,
                'deadline' => $data['deadline'] ?? null,
                'creator_id' => $data['creator_id']
            ]);

            $taskId = (int)$this->db->lastInsertId();

            // Insert Assignees
            if (!empty($assignees)) {
                $stmt = $this->db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
                foreach ($assignees as $userId) {
                    $stmt->execute([$taskId, $userId]);
                }
            }

            // Log activity
            $this->logAudit('create', 'tasks', $taskId, null, $data);

            // Notify Assignees
            if (!empty($assignees)) {
                $notificationRepo = new \Modules\Notifications\NotificationRepository();
                $performer = \Core\Session::get('username', 'Someone');
                $dueDate = $data['deadline'] ? date('d/m/Y', strtotime($data['deadline'])) : 'No deadline';
                $msg = "Task \"{$data['title']}\" assigned to you by {$performer}. Due: {$dueDate}";
                
                $notificationRepo->createForUsers(
                    $assignees,
                    'task',
                    $msg,
                    $data['priority'],
                    ['task_id' => $taskId]
                );
            }

            if ($useTransaction) $this->db->commit();
            return $taskId;
        } catch (Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update task status with workflow rules and recurrence.
     */
    public function updateStatus(int $taskId, string $newStatus): array
    {
        $task = $this->getTaskById($taskId);
        if (!$task) return ['success' => false, 'message' => 'Task not found.'];

        // Workflow Rule: Must be "Doing" before "Done"
        if ($newStatus === 'done' && $task['status'] !== 'doing') {
            throw new Exception('Task must be in "Doing" status before it can be marked as "Done".');
        }

        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET status = ? WHERE id = ?");
            $result = $stmt->execute([$newStatus, $taskId]);
            
            $recurrenceCreated = false;
            if ($result) {
                $this->logAudit('update_status', 'tasks', $taskId, ['status' => $task['status']], ['status' => $newStatus]);
                
                // Notify involved parties (Creator + Assignees)
                $involvedIds = array_column($task['assignees'], 'id');
                $involvedIds[] = $task['creator_id'];
                $involvedIds = array_unique(array_map('intval', $involvedIds));
                
                // Remove the performer from the list to avoid self-notification
                $performerId = (int)\Core\Session::get('user_id');
                $notifyIds = array_filter($involvedIds, fn($id) => $id !== $performerId);

                if (!empty($notifyIds)) {
                    $notificationRepo = new \Modules\Notifications\NotificationRepository();
                    $performer = \Core\Session::get('username', 'Someone');
                    $statusName = ucfirst(str_replace('_', ' ', $newStatus));
                    $msg = "Task \"{$task['title']}\" status changed to {$statusName} by {$performer}";
                    
                    $notificationRepo->createForUsers(
                        array_values($notifyIds),
                        'task',
                        $msg,
                        $task['priority'],
                        ['task_id' => $taskId]
                    );
                }

                // If marked Done and is recurring, create the next occurrence
                if ($newStatus === 'done' && $task['recurrence'] !== 'none') {
                    $this->handleRecurrence($task);
                    $recurrenceCreated = true;
                }
            }
            
            if ($useTransaction) $this->db->commit();
            return [
                'success' => $result,
                'recurrence' => $recurrenceCreated,
                'message' => $recurrenceCreated ? 'Task completed! New recurring task has been created.' : 'Task status updated.'
            ];
        } catch (Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Calculate and create the next occurrence of a recurring task.
     */
    private function handleRecurrence(array $task): void
    {
        $meta = is_string($task['recurrence_metadata']) 
            ? json_decode($task['recurrence_metadata'], true) 
            : $task['recurrence_metadata'];
        
        $nextDeadline = new \DateTime($task['deadline'] ?? 'now');

        if ($task['recurrence'] === 'daily') {
            $nextDeadline->modify('+1 day');
            if (!empty($meta['time'])) {
                list($h, $m) = explode(':', $meta['time']);
                $nextDeadline->setTime((int)$h, (int)$m);
            }
        } elseif ($task['recurrence'] === 'weekly') {
            $nextDeadline->modify('+1 week');
            if (!empty($meta['day'])) {
                // Map day string to DateTime modifier
                $nextDeadline->modify('next ' . $meta['day']);
            }
        } elseif ($task['recurrence'] === 'monthly') {
            $nextDeadline->modify('+1 month');
            if (!empty($meta['date'])) {
                $day = min((int)$meta['date'], (int)$nextDeadline->format('t'));
                $nextDeadline->setDate((int)$nextDeadline->format('Y'), (int)$nextDeadline->format('m'), $day);
            }
        }

        // Prepare data for the next task
        $stmt = $this->db->prepare("
            INSERT INTO tasks (title, description, status, priority, recurrence, recurrence_metadata, deadline, creator_id)
            VALUES (:title, :description, 'todo', :priority, :recurrence, :recurrence_metadata, :deadline, :creator_id)
        ");
        
        $stmt->execute([
            'title' => $task['title'],
            'description' => $task['description'],
            'priority' => $task['priority'],
            'recurrence' => $task['recurrence'],
            'recurrence_metadata' => is_array($task['recurrence_metadata']) ? json_encode($task['recurrence_metadata']) : $task['recurrence_metadata'],
            'deadline' => $nextDeadline->format('Y-m-d H:i:s'),
            'creator_id' => $task['creator_id']
        ]);

        $newTaskId = (int)$this->db->lastInsertId();

        // Copy Assignees
        if (!empty($task['assignees'])) {
            $assigneeStmt = $this->db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
            foreach ($task['assignees'] as $u) {
                $assigneeStmt->execute([$newTaskId, $u['id']]);
            }
        }

        $this->logAudit('create', 'tasks', $newTaskId, null, ['title' => '[Auto-Generated] ' . $task['title']]);
    }

    /**
     * Update task details and assignees.
     */
    public function updateTask(int $id, array $data, array $assignees): bool
    {
        $oldTask = $this->getTaskById($id);
        if (!$oldTask) return false;

        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE tasks SET 
                title = :title, description = :description, status = :status, 
                priority = :priority, recurrence = :recurrence, 
                recurrence_metadata = :recurrence_metadata, deadline = :deadline
                WHERE id = :id
            ");
            
            $stmt->execute([
                'id' => $id,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
                'priority' => $data['priority'],
                'recurrence' => $data['recurrence'],
                'recurrence_metadata' => $data['recurrence_metadata'] ?? null,
                'deadline' => $data['deadline'] ?: null
            ]);

            // Sync Assignees
            $oldAssigneeIds = array_column($oldTask['assignees'], 'id');
            $this->db->prepare("DELETE FROM task_assignees WHERE task_id = ?")->execute([$id]);
            if (!empty($assignees)) {
                $stmt = $this->db->prepare("INSERT INTO task_assignees (task_id, user_id) VALUES (?, ?)");
                foreach ($assignees as $userId) {
                    $stmt->execute([$id, (int)$userId]);
                }
            }

            // Notifications
            $notificationRepo = new \Modules\Notifications\NotificationRepository();
            
            // 1. Notify newly added assignees
            $newAssigneeIds = array_diff($assignees, $oldAssigneeIds);
            if (!empty($newAssigneeIds)) {
                $performer = \Core\Session::get('username', 'Someone');
                $dueDate = $data['deadline'] ? date('d/m/Y', strtotime($data['deadline'])) : 'No deadline';
                $msg = "Task \"{$data['title']}\" assigned to you by {$performer}. Due: {$dueDate}";

                $notificationRepo->createForUsers(
                    $newAssigneeIds,
                    'task',
                    $msg,
                    $data['priority'],
                    ['task_id' => $id]
                );
            }

            // 2. Notify involved parties if title or description changed
            if ($oldTask['title'] !== $data['title'] || $oldTask['description'] !== $data['description']) {
                $involvedIds = array_unique(array_merge($assignees, [(int)$oldTask['creator_id']]));
                $performerId = (int)\Core\Session::get('user_id');
                $notifyIds = array_filter($involvedIds, fn($uid) => (int)$uid !== $performerId);

                if (!empty($notifyIds)) {
                    $performer = \Core\Session::get('username', 'Someone');
                    $msg = "Task \"{$data['title']}\" details updated by {$performer}";

                    $notificationRepo->createForUsers(
                        array_values($notifyIds),
                        'task',
                        $msg,
                        $data['priority'],
                        ['task_id' => $id]
                    );
                }
            }

            // Log activity if status changed
            if ($oldTask['status'] !== $data['status']) {
                $this->logAudit('update_status', 'tasks', $id, ['status' => $oldTask['status']], ['status' => $data['status']]);
            }
            $this->logAudit('update', 'tasks', $id, $oldTask, $data);

            if ($useTransaction) $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Add file attachment metadata.
     */
    public function addAttachment(int $taskId, string $path, string $name, string $type): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO task_attachments (task_id, file_path, file_name, file_type)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$taskId, $path, $name, $type]);
    }

    /**
     * Delete a task.
     */
    public function deleteTask(int $id): bool
    {
        $task = $this->getTaskById($id);
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
        $result = $stmt->execute([$id]);
        if ($result && $task) {
            $this->logAudit('delete', 'tasks', $id, $task, null);
        }
        return $result;
    }

    /**
     * Get comments for a task.
     */
    public function getComments(int $taskId): array
    {
        $stmt = $this->db->prepare("
            SELECT tc.*, u.username, u.profile_photo 
            FROM task_comments tc
            JOIN users u ON tc.user_id = u.id
            WHERE tc.task_id = ?
            ORDER BY tc.created_at DESC
        ");
        $stmt->execute([$taskId]);
        return $stmt->fetchAll();
    }

    /**
     * Add a comment to a task.
     */
    public function addComment(int $taskId, int $userId, string $comment): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO task_comments (task_id, user_id, comment)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$taskId, $userId, $comment]);
        $commentId = (int)$this->db->lastInsertId();

        // Notify Assignees
        $task = $this->getTaskById($taskId);
        if ($task && !empty($task['assignees'])) {
            $assigneeIds = array_column($task['assignees'], 'id');
            // Remove the commenter from the notification list
            $notifyIds = array_filter($assigneeIds, fn($id) => (int)$id !== $userId);
            
            if (!empty($notifyIds)) {
                $notificationRepo = new \Modules\Notifications\NotificationRepository();
                $performer = \Core\Session::get('username', 'Someone');
                $msg = "New comment on task \"{$task['title']}\" by {$performer}";
                
                $notificationRepo->createForUsers(
                    $notifyIds,
                    'task',
                    $msg,
                    $task['priority'],
                    ['task_id' => $taskId]
                );
            }
        }

        return $commentId;
    }
}
