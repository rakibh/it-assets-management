<?php

declare(strict_types=1);

namespace Modules\Auth;

use Core\Session;
use Exception;

class UserController
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * List users (Admin only).
     */
    public function list(): array
    {
        if (Session::get('role') !== 'admin') {
            throw new Exception("Unauthorized access.");
        }

        $page = (int)($_GET['page'] ?? 1);
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortDir = $_GET['sort_dir'] ?? 'DESC';
        $search = $_GET['search'] ?? null;

        $settingsRepo = new \Modules\Admin\SettingsRepository();
        $limit = (int)$settingsRepo->get('records_per_page', 20);

        $result = $this->userRepository->getUsers($page, $limit, $sortBy, $sortDir, $search);

        // If AJAX request, return only the partial table body
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: text/html');
            $data = [
                'users' => $result['users'],
                'total' => $result['total'],
                'pages' => $result['pages'],
                'currentPage' => $page,
                'current_page' => $page
            ];
            require __DIR__ . '/../../views/users/partial_list.php';
            exit;
        }

        return [
            'title' => 'User Management',
            'view' => 'views/users/list.php',
            'data' => [
                'users' => $result['users'],
                'total' => $result['total'],
                'pages' => $result['pages'],
                'currentPage' => $page,
                'current_page' => $page,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'search' => $search
            ]
        ];
    }

    /**
     * Get revision history for AJAX.
     */
    public function revisionHistory(int $id): array
    {
        if (Session::get('role') !== 'admin') {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }
        $table = $_GET['table'] ?? 'users';
        return [
            'success' => true,
            'revisions' => $this->userRepository->getRevisionHistory($table, $id)
        ];
    }

    /**
     * Store/Update user logic.
     */
    public function store(array $data): array
    {
        if (Session::get('role') !== 'admin') {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $userId = (int)($data['user_id'] ?? $data['id'] ?? 0);
            
            // Password Policy Check (Only for new user or if password provided for edit)
            if (!$userId || !empty($data['password'])) {
                $pw = $data['password'] ?? '';
                if (strlen($pw) < 6 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/[0-9]/', $pw) || !preg_match('/[^A-Za-z0-9]/', $pw)) {
                    return ['success' => false, 'message' => 'Password must be min 6 chars, include a letter, a number, and a special char.'];
                }
            }

            if ($userId) {
                $success = $this->userRepository->updateUser($userId, $data);
                if (!$success) {
                    return ['success' => false, 'message' => 'Failed to update user or user not found.'];
                }
                (new \Modules\Admin\AdminRepository())->logEvent('info', 'user', "User updated: " . ($data['username'] ?? $userId), ['target_user_id' => $userId]);
                $msg = isset($data['force_password_change']) ? 'Password reset successfully.' : 'User updated successfully.';
            } else {
                $newId = $this->userRepository->createUser($data);
                (new \Modules\Admin\AdminRepository())->logEvent('info', 'user', "New user created: " . $data['username'], ['target_user_id' => $newId]);
                $msg = 'User created successfully.';
            }

            return ['success' => true, 'message' => $msg, 'redirect' => 'index.php?route=list_users'];
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'SQLSTATE[23000]') !== false && strpos($msg, '1062') !== false) {
                // Prettier duplicate entry message
                if (preg_match("/Duplicate entry '(.*)' for key '(.*)'/", $msg, $matches)) {
                    $msg = "Duplicate entry '{$matches[1]}' for '{$matches[2]}'";
                }
            }
            return ['success' => false, 'message' => $msg];
        }
    }

    /**
     * User Profile view.
     */
    public function profile(): array
    {
        $id = (int)Session::get('user_id');
        $user = $this->userRepository->getUserById($id);
        if ($user) {
            unset($user['password']); // Never send password hash to the client
        }
        return [
            'title' => 'My Profile',
            'view' => 'views/users/profile.php',
            'data' => [
                'user' => $user
            ]
        ];
    }

    /**
     * Update own profile logic.
     */
    public function updateProfile(array $data): array
    {
        if (!Session::get('user_id')) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $userId = (int)Session::get('user_id');
            if (!$userId) {
                return ['success' => false, 'message' => 'Invalid session user ID.'];
            }
            $user = $this->userRepository->getUserById($userId);
            
            $updateData = [];

            // 1. Password Change
            if (!empty($data['password'])) {
                // Verify current password first if NOT a forced change (or just always for security as per PRD)
                if (empty($data['current_password']) && !Session::get('force_pw')) {
                    return ['success' => false, 'message' => 'Current password is required to set a new one.'];
                }

                if (!empty($data['current_password']) && !password_verify($data['current_password'], $user['password'])) {
                    return ['success' => false, 'message' => 'Current password incorrect.'];
                }

                $pw = $data['password'];
                if (strlen($pw) < 6 || !preg_match('/[A-Za-z]/', $pw) || !preg_match('/[0-9]/', $pw) || !preg_match('/[^A-Za-z0-9]/', $pw)) {
                    return ['success' => false, 'message' => 'New password must be min 6 chars, include a letter, a number, and a special char.'];
                }

                $updateData['password'] = $pw;
                $updateData['force_password_change'] = 0; // Clear the flag
                Session::set('force_pw', false);
            }

            // 2. Personal Info (Editable as per PRD)
            $editable = ['first_name', 'last_name', 'designation', 'username', 'email', 'phone', 'profile_photo'];
            foreach ($editable as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (empty($updateData)) {
                return ['success' => true, 'message' => 'No changes made.'];
            }

            $this->userRepository->updateUser($userId, $updateData);

            // Refresh Session Data
            if (isset($updateData['profile_photo'])) {
                Session::set('profile_photo', $updateData['profile_photo']);
            }
            if (isset($updateData['username'])) {
                Session::set('username', $updateData['username']);
            }

            return ['success' => true, 'message' => 'Profile updated successfully.'];
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'SQLSTATE[23000]') !== false && strpos($msg, '1062') !== false) {
                // Prettier duplicate entry message
                if (preg_match("/Duplicate entry '(.*)' for key '(.*)'/", $msg, $matches)) {
                    $msg = "Duplicate entry '{$matches[1]}' for '{$matches[2]}'";
                }
            }
            return ['success' => false, 'message' => $msg];
        }
    }

    /**
     * Export users to CSV.
     */
    public function export(): void
    {
        if (Session::get('role') !== 'admin') {
            throw new Exception("Unauthorized.");
        }

        $result = $this->userRepository->getUsers(1, 1000, 'username', 'ASC');
        $users = $result['users'];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Username', 'Employee ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Designation', 'Role', 'Status', 'Created At']);

        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'], $user['username'], $user['employee_id'],
                $user['first_name'], $user['last_name'], $user['email'],
                $user['phone'], $user['designation'], $user['role'],
                $user['status'], $user['created_at']
            ]);
        }
        fclose($output);
        exit;
    }
}
