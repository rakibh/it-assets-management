<?php

declare(strict_types=1);

namespace Modules\Auth;

use Core\Repository;
use Core\Session;
use PDO;
use Exception;

class UserRepository extends Repository
{
    /**
     * Get users with pagination, sorting, and search.
     */
    public function getUsers(int $page = 1, int $limit = 20, string $sortBy = 'created_at', string $sortDir = 'DESC', ?string $search = null): array
    {
        $offset = ($page - 1) * $limit;
        $allowedSort = ['role', 'status', 'created_at', 'username', 'employee_id'];
        if (!in_array($sortBy, $allowedSort)) $sortBy = 'created_at';
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $where = "";
        $params = [];
        if ($search) {
            $where = " WHERE (username LIKE :s1 OR first_name LIKE :s2 OR last_name LIKE :s3 OR email LIKE :s4 OR employee_id LIKE :s5)";
            $params['s1'] = '%' . $search . '%';
            $params['s2'] = '%' . $search . '%';
            $params['s3'] = '%' . $search . '%';
            $params['s4'] = '%' . $search . '%';
            $params['s5'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare("
            SELECT id, username, employee_id, first_name, last_name, email, phone, designation, profile_photo, role, status, force_password_change, created_at 
            FROM users 
            $where
            ORDER BY $sortBy $sortDir 
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)max(0, $offset), PDO::PARAM_INT);
        $stmt->execute();
        
        $users = $stmt->fetchAll();

        // Get total count for pagination
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        return [
            'users' => $users,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * Get user by ID with all fields.
     */
    public function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Get all active admin IDs.
     */
    public function getAdminIds(): array
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Create a new user with audit logging.
     */
    public function createUser(array $data): int
    {
        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("
                INSERT INTO users (username, employee_id, password, first_name, last_name, email, phone, designation, profile_photo, role, status)
                VALUES (:username, :employee_id, :password, :first_name, :last_name, :email, :phone, :designation, :profile_photo, :role, :status)
            ");
            
            $stmt->execute([
                'username'    => $data['username'],
                'employee_id' => $data['employee_id'],
                'password'    => password_hash($data['password'], PASSWORD_DEFAULT),
                'first_name'  => $data['first_name'] ?? null,
                'last_name'   => $data['last_name'] ?? null,
                'email'       => $data['email'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'designation' => $data['designation'] ?? null,
                'profile_photo' => $data['profile_photo'] ?? null,
                'role'        => $data['role'] ?? 'user',
                'status'      => $data['status'] ?? 'active'
            ]);

            $userId = (int)$this->db->lastInsertId();
            
            $this->logAudit('create', 'users', $userId, null, $data);
            
            // Notify Admins
            $adminIds = $this->getAdminIds();
            $notificationRepo = new \Modules\Notifications\NotificationRepository();
            $performer = \Core\Session::get('username', 'Admin');
            $role = ucfirst($data['role'] ?? 'user');
            $msg = "User {$data['username']} created as {$role} by {$performer}";
            
            $notificationRepo->createForUsers(
                $adminIds, 
                'user', 
                $msg, 
                'medium', 
                ['user_id' => $userId]
            );

            if ($useTransaction) $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Update user and log changes.
     */
    public function updateUser(int $id, array $data): bool
    {
        $oldUser = $this->getUserById($id);
        if (!$oldUser) return false;

        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $fields = [];
            $params = ['id' => $id];

            $updateable = [
                'username', 'employee_id', 'first_name', 'last_name', 
                'email', 'phone', 'designation', 'profile_photo', 
                'role', 'status', 'force_password_change'
            ];

            foreach ($updateable as $key) {
                // Check if key exists in data
                if (array_key_exists($key, $data)) {
                    $fields[] = "$key = :$key";
                    $params[$key] = $data[$key];
                }
            }

            if (!empty($data['password'])) {
                $fields[] = "password = :password";
                $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (empty($fields)) {
                if ($useTransaction) $this->db->commit();
                return true;
            }

            $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id");
            $stmt->execute($params);

            // Notify Admins if role or status changed
            $notificationRepo = new \Modules\Notifications\NotificationRepository();
            $performer = \Core\Session::get('username', 'Admin');
            $adminIds = $this->getAdminIds();

            if (isset($data['role']) && $oldUser['role'] !== $data['role']) {
                $role = ucfirst($data['role']);
                $msg = "Role changed for {$oldUser['username']} to {$role} by {$performer}";
                $notificationRepo->createForUsers($adminIds, 'user', $msg, 'high', ['user_id' => $id]);
            }

            if (isset($data['status']) && $oldUser['status'] !== $data['status'] && $data['status'] === 'inactive') {
                $msg = "Account {$oldUser['username']} deactivated by {$performer}";
                $notificationRepo->createForUsers($adminIds, 'user', $msg, 'high', ['user_id' => $id]);
            }

            // Calculate changes for audit log
            $newValues = array_intersect_key(array_merge($oldUser, $data), array_flip($updateable));
            $this->logAudit('update', 'users', $id, $oldUser, $newValues);

            if ($useTransaction) $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delete a user.
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->getUserById($id);
        if (!$user) return false;

        $useTransaction = !$this->db->inTransaction();
        if ($useTransaction) $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$id]);

            if ($result) {
                // Notify Admins
                $adminIds = $this->getAdminIds();
                $notificationRepo = new \Modules\Notifications\NotificationRepository();
                $performer = \Core\Session::get('username', 'Admin');
                $msg = "User {$user['username']} deleted by {$performer}";

                $notificationRepo->createForUsers($adminIds, 'user', $msg, 'high');
                
                $this->logAudit('delete', 'users', $id, $user, null);
            }

            if ($useTransaction) $this->db->commit();
            return $result;
        } catch (Exception $e) {
            if ($useTransaction) $this->db->rollBack();
            throw $e;
        }
    }
}
