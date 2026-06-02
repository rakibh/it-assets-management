<?php

declare(strict_types=1);

namespace Modules\Tasks;

use Core\Session;
use Exception;

class TaskController
{
    private TaskRepository $taskRepository;

    public function __construct()
    {
        $this->taskRepository = new TaskRepository();
    }

    /**
     * List tasks with Tab and Board logic.
     */
    public function list(): array
    {
        $page = (int)($_GET['page'] ?? 1);
        $tab = $_GET['tab'] ?? 'board';
        $filters = [
            'tab' => $tab,
            'priority' => $_GET['priority'] ?? null,
            'search' => $_GET['search'] ?? null,
            'assignee_id' => $_GET['assignee_id'] ?? null,
            'sort_by' => $_GET['sort_by'] ?? 'deadline',
            'sort_dir' => $_GET['sort_dir'] ?? 'ASC'
        ];

        $settingsRepo = new \Modules\Admin\SettingsRepository();
        $limit = ($tab === 'all') 
            ? (int)$settingsRepo->get('records_per_page', 20)
            : 1000; // No limitation for board/other tabs

        $res = $this->taskRepository->getTasks($filters, $page, $limit);

        return [
            'title' => 'Task Management',
            'view' => 'views/tasks/list.php',
            'data' => [
                'tasks' => $res['items'],
                'total' => $res['total'],
                'pages' => $res['pages'],
                'currentPage' => $page,
                'counts' => $this->taskRepository->getStatusCounts(),
                'filters' => $filters,
                'users' => (new \Modules\Auth\UserRepository())->getUsers(1, 1000)['users']
            ]
        ];
    }

    /**
     * Show edit task form.
     */
    public function edit(int $id): array
    {
        $task = $this->taskRepository->getTaskById($id);
        if (!$task) throw new Exception("Task not found.");

        $this->ensureTaskPermission($task, 'edit');

        return [
            'title' => 'Edit Task: ' . $task['title'],
            'view' => 'views/tasks/add_edit.php',
            'data' => [
                'task' => $task,
                'isEdit' => true,
                'assigneeIds' => array_column($task['assignees'], 'id'),
                'users' => (new \Modules\Auth\UserRepository())->getUsers(1, 1000)['users']
            ]
        ];
    }

    /**
     * Show add task form.
     */
    public function add(): array
    {
        return [
            'title' => 'Add New Task',
            'view' => 'views/tasks/add_edit.php',
            'data' => [
                'task' => null,
                'isEdit' => false,
                'assigneeIds' => [],
                'users' => (new \Modules\Auth\UserRepository())->getUsers(1, 1000)['users']
            ]
        ];
    }

    /**
     * Handle task storage (New).
     */
    public function store(array $data): array
    {
        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $taskData = $this->sanitizeTaskData($data);
            $assignees = $data['assignees'] ?? [];
            
            $taskId = $this->taskRepository->createTask($taskData, $assignees);
            (new \Modules\Admin\AdminRepository())->logEvent('info', 'task', "New task created: " . $taskData['title'], ['task_id' => $taskId]);

            // Handle Uploads if any
            if (!empty($_FILES['attachments'])) {
                $this->handleUploads($taskId, $_FILES['attachments']);
            }

            return ['success' => true, 'message' => 'Task created.', 'redirect' => 'index.php?route=list_tasks'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Handle task update.
     */
    public function update(array $data): array
    {
        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $id = (int)($data['task_id'] ?? 0);
            $task = $this->taskRepository->getTaskById($id);
            if (!$task) throw new Exception("Task not found.");

            // PRD: Only Admin, Creator or Assignee can edit
            $this->ensureTaskPermission($task, 'edit');

            // PRD 5.3a: Admin/Creator-only capability to extend deadlines
            if (!empty($data['deadline']) && $data['deadline'] !== $task['deadline']) {
                $isAdmin = Session::get('role') === 'admin';
                $isCreator = (int)$task['creator_id'] === (int)Session::get('user_id');
                if (!$isAdmin && !$isCreator) {
                    return ['success' => false, 'message' => 'Only administrators or the task creator can extend deadlines.'];
                }
            }

            $taskData = $this->sanitizeTaskData($data);
            $assignees = $data['assignees'] ?? [];

            $this->taskRepository->updateTask($id, $taskData, $assignees);
            (new \Modules\Admin\AdminRepository())->logEvent('info', 'task', "Task updated: " . ($taskData['title'] ?? $id), ['task_id' => $id]);

            // Handle Uploads
            if (!empty($_FILES['attachments'])) {
                $this->handleUploads($id, $_FILES['attachments']);
            }

            return ['success' => true, 'message' => 'Task updated.', 'redirect' => 'index.php?route=list_tasks'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * View task details.
     */
    public function view(int $id): array
    {
        $task = $this->taskRepository->getTaskById($id);
        if (!$task) throw new Exception("Task not found.");

        return [
            'title' => 'Task Details',
            'view' => 'views/tasks/view.php',
            'data' => [
                'task' => $task,
                'logs' => $this->taskRepository->getRevisionHistory('tasks', $id)
            ]
        ];
    }
    private function sanitizeTaskData(array $data): array
    {
        return [
            'title' => trim($data['title'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'status' => $data['status'] ?? 'todo',
            'priority' => $data['priority'] ?? 'medium',
            'recurrence' => $data['recurrence'] ?? 'none',
            'recurrence_metadata' => $data['recurrence_metadata'] ?? null,
            'deadline' => $data['deadline'] ?: null,
            'creator_id' => Session::get('user_id')
        ];
    }

    private function handleUploads(int $taskId, array $files): void
    {
        $settingsRepo = new \Modules\Admin\SettingsRepository();
        $maxSizeMB = (int)$settingsRepo->get('task_max_upload_size', '5');
        $allowedExtStr = $settingsRepo->get('task_allowed_extensions', 'pdf,jpg,png,jpeg,doc,docx,txt,xls,xlsx,csv');
        $allowedExtensions = array_map('trim', explode(',', strtolower($allowedExtStr)));

        $maxSizeBytes = $maxSizeMB * 1024 * 1024;

        $storageDir = __DIR__ . '/../../storage/uploads/tasks/' . $taskId . '/';
        if (!is_dir($storageDir)) mkdir($storageDir, 0777, true);

        foreach ($files['name'] as $key => $name) {
            if ($files['error'][$key] === UPLOAD_ERR_OK) {
                $size = $files['size'][$key];
                $tmp = $files['tmp_name'][$key];
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                // Validate: Settings-based
                if ($size > $maxSizeBytes) continue;
                if (!in_array($extension, $allowedExtensions)) continue;

                $fileName = time() . '_' . basename($name);
                $destination = $storageDir . $fileName;

                if (move_uploaded_file($tmp, $destination)) {
                    $type = $files['type'][$key];
                    $this->taskRepository->addAttachment($taskId, 'storage/uploads/tasks/' . $taskId . '/' . $fileName, $name, $type);
                }
            }
        }
    }

    /**
     * Update task status (AJAX).
     */
    public function updateStatus(array $data): array
    {
        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $taskId = (int)($data['task_id'] ?? 0);
            $task = $this->taskRepository->getTaskById($taskId);
            if (!$task) throw new Exception("Task not found.");

            // PRD 5.2: Any involved party (Task Creator, Assignee, or Admin)
            $this->ensureTaskPermission($task, 'update status of');

            $newStatus = $data['status'] ?? '';
            $result = $this->taskRepository->updateStatus($taskId, $newStatus);

            return $result;
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Add comment to task (AJAX).
     */
    public function addComment(array $data): array
    {
        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $taskId = (int)($data['task_id'] ?? 0);
            $comment = trim($data['comment'] ?? '');
            $userId = (int)Session::get('user_id');

            if (empty($comment)) throw new Exception("Comment cannot be empty.");

            $this->taskRepository->addComment($taskId, $userId, $comment);

            return ['success' => true, 'message' => 'Comment added.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete a task.
     */
    public function delete(int $id): array
    {
        try {
            $task = $this->taskRepository->getTaskById($id);
            if (!$task) throw new Exception("Task not found.");

            // Standard: Only Admin or Creator can delete
            if (Session::get('role') !== 'admin' && (int)Session::get('user_id') !== (int)$task['creator_id']) {
                return ['success' => false, 'message' => 'Only administrators or the task creator can delete tasks.'];
            }

            $this->taskRepository->deleteTask($id);
            (new \Modules\Admin\AdminRepository())->logEvent('info', 'task', "Task deleted (ID: $id)");
            return ['success' => true, 'message' => 'Task deleted.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Export tasks to CSV.
     */
    public function export(): void
    {
        $filters = [
            'tab' => $_GET['tab'] ?? 'board',
            'priority' => $_GET['priority'] ?? null,
            'search' => $_GET['search'] ?? null,
            'assignee_id' => $_GET['assignee_id'] ?? null,
            'sort_by' => $_GET['sort_by'] ?? 'deadline',
            'sort_dir' => $_GET['sort_dir'] ?? 'ASC'
        ];

        $res = $this->taskRepository->getTasks($filters, 1, 10000); // Higher limit for export
        $tasks = $res['items'];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Tasks_Export_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Title', 'Description', 'Priority', 'Status', 'Deadline', 'Creator', 'Assignees', 'Tags', 'Created At']);

        foreach ($tasks as $task) {
            fputcsv($output, [
                $task['title'],
                $task['description'],
                ucfirst($task['priority']),
                ucfirst(str_replace('_', ' ', $task['status'])),
                $task['deadline'] ? date('d/m/Y', strtotime($task['deadline'])) : 'No Deadline',
                $task['creator_name'],
                $task['assignee_names'] ?? 'Unassigned',
                $task['tags'] ?? '',
                date('d/m/Y H:i', strtotime($task['created_at']))
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Helper to verify task permissions.
     */
    private function ensureTaskPermission(array $task, string $action): void
    {
        $userId = (int)Session::get('user_id');
        $isAdmin = Session::get('role') === 'admin';
        $isCreator = (int)$task['creator_id'] === $userId;
        
        $assigneeIds = array_column($task['assignees'], 'id');
        $isAssignee = in_array($userId, $assigneeIds);

        if (!$isAdmin && !$isCreator && !$isAssignee) {
            throw new Exception("You do not have permission to " . $action . " this task.");
        }
    }
}
