<?php

declare(strict_types=1);

/**
 * IT Management System - Entry Point
 */

// 1. Simple Autoloader
spl_autoload_register(function ($class) {
    $prefix = '';
    $base_dir = __DIR__ . '/';

    $file = $base_dir . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

use Core\Env;
use Core\Session;
use Core\I18n;
use Modules\Auth\LoginController;
use Modules\Auth\UserController;
use Modules\Tasks\TaskController;
use Modules\Network\NetworkController;
use Modules\Admin\SettingsController;
use Modules\Admin\AdminController;
use Modules\Dashboard\DashboardController;
use Modules\Notifications\NotificationController;
use Modules\Equipment\EquipmentController;
use Modules\Auth\PreferencesController;

// 2. Load Environment Variables
Env::load(__DIR__ . '/.env');

// 3. Set Timezone
date_default_timezone_set(Env::get('TIMEZONE', 'Asia/Dhaka'));

// 4. Start Secure Session
Session::start();

// 5. Initialize Internationalization
I18n::init();

// 6. Basic Routing
$route = $_GET['route'] ?? 'dashboard';

// Force Password Change Check
if (Session::get('user_id') && Session::get('force_pw') && !in_array($route, ['profile', 'update_profile', 'logout', 'change_lang'])) {
    header('Location: index.php?route=profile&force=1');
    exit;
}

try {
    switch ($route) {
        // Auth & User Routes
        case 'login':
            require __DIR__ . '/views/auth/login.php';
            break;

        case 'login_submit':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new LoginController())->login($data ?? []));
            break;

        case 'logout':
            (new LoginController())->logout();
            break;

        case 'list_users':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            if (isset($_GET['export']) && $_GET['export'] === 'csv') {
                (new UserController())->export();
                exit;
            }
            $res = (new UserController())->list();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'user_store':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new UserController())->store($data ?? []));
            break;

        case 'update_profile':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new UserController())->updateProfile($data ?? []));
            break;

        case 'upload_photo':
            header('Content-Type: application/json');
            echo json_encode((new \Core\UploadController())->handleUserPhoto($_FILES['photo'] ?? null));
            break;

        case 'user_revision_history':
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode((new UserController())->revisionHistory($id));
            break;

        case 'delete_user':
            if (!Session::get('user_id') || Session::get('role') !== 'admin') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            $id = (int)($_GET['id'] ?? 0);
            if ($id === (int)Session::get('user_id')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
                exit;
            }
            try {
                $success = (new \Modules\Auth\UserRepository())->deleteUser($id);
                if ($success) {
                    (new \Modules\Admin\AdminRepository())->logEvent('info', 'user', "User deleted (ID: $id)");
                }
                header('Content-Type: application/json');
                echo json_encode(['success' => $success, 'message' => $success ? 'User deleted successfully' : 'User not found']);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $e->getMessage()]);
            }
            exit;
            break;

        case 'profile':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new UserController())->profile();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;
            
        // Task Routes
        case 'list_tasks':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new TaskController())->list();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'add_task':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new TaskController())->add();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'edit_task':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $id = (int)($_GET['id'] ?? 0);
            $res = (new TaskController())->edit($id);
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'view_task':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $id = (int)($_GET['id'] ?? 0);
            $res = (new TaskController())->view($id);
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'task_store':
            header('Content-Type: application/json');
            echo json_encode((new TaskController())->store($_POST));
            break;

        case 'task_update':
            header('Content-Type: application/json');
            echo json_encode((new TaskController())->update($_POST));
            break;

        case 'task_update_status':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new TaskController())->updateStatus($data ?? []));
            break;

        case 'task_add_comment':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new TaskController())->addComment($data ?? []));
            break;

        case 'task_delete':
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode((new TaskController())->delete($id));
            break;

        case 'task_export':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            (new TaskController())->export();
            break;

        // Network Routes
        case 'list_network':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new NetworkController())->list();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'view_network':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $id = (int)($_GET['id'] ?? 0);
            $res = (new NetworkController())->view($id);
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'network_store':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new NetworkController())->store($data ?? []));
            break;

        case 'network_delete':
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode((new NetworkController())->delete($id));
            break;

        case 'network_export':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            (new NetworkController())->export();
            break;

        // Equipment Routes
        case 'list_equipment':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new EquipmentController())->list();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'add_equipment':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new EquipmentController())->add();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'edit_equipment':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $id = (int)($_GET['id'] ?? 0);
            $res = (new EquipmentController())->edit($id);
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'view_equipment':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $id = (int)($_GET['id'] ?? 0);
            $res = (new EquipmentController())->view($id);
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'equipment_save':
            header('Content-Type: application/json');
            // Support both JSON (AJAX) and POST (FormData)
            $data = $_POST;
            if (empty($data)) {
                $data = json_decode(file_get_contents('php://input'), true) ?? [];
            }
            echo json_encode((new EquipmentController())->save($data));
            break;

        case 'equipment_delete':
            if (!Session::get('user_id')) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode((new EquipmentController())->delete($id));
            break;

        case 'equipment_bulk_delete':
            if (!Session::get('user_id')) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new EquipmentController())->bulkDelete($data ?? []));
            break;

        case 'equipment_types':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new EquipmentController())->types();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'equipment_type_save':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new EquipmentController())->saveType($data ?? []));
            break;

        case 'equipment_type_delete':
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode((new EquipmentController())->deleteType($id));
            break;

        case 'equipment_export':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            (new EquipmentController())->export();
            break;

        case 'equipment_qr_data':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            header('Content-Type: application/json');
            echo json_encode((new EquipmentController())->getQrData($_GET['ids'] ?? ''));
            break;

        case 'equipment_blocks':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new EquipmentController())->blocks();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'equipment_block_save':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new EquipmentController())->saveBlock($data ?? []));
            break;

        case 'equipment_block_delete':
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode((new EquipmentController())->deleteBlock($id));
            break;

        // Admin/System Routes
        case 'settings':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new SettingsController())->index();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'settings_save':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new SettingsController())->save($data ?? []));
            break;

        case 'admin_logs':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new AdminController())->logs();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'admin_tools':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new AdminController())->tools();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'admin_clear_logs':
            header('Content-Type: application/json');
            echo json_encode((new AdminController())->clearLogs());
            break;

        case 'admin_log_detail':
            header('Content-Type: application/json');
            echo json_encode((new AdminController())->logDetail());
            break;

        case 'admin_optimize_db':
            header('Content-Type: application/json');
            echo json_encode((new AdminController())->optimizeDB());
            break;

        case 'admin_cleanup_cache':
            header('Content-Type: application/json');
            echo json_encode((new AdminController())->cleanupCache());
            break;

        case 'admin_backup_db':
            (new AdminController())->backupDB();
            break;

        // Notification Routes
        case 'list_notifications':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new NotificationController())->list();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'notifications_poll':
            header('Content-Type: application/json');
            echo json_encode((new NotificationController())->poll());
            break;

        case 'notification_mark_read':
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode((new NotificationController())->markRead($id));
            break;

        case 'notification_mark_all_read':
            header('Content-Type: application/json');
            echo json_encode((new NotificationController())->markAllRead());
            break;

        case 'notification_acknowledge':
            header('Content-Type: application/json');
            $id = (int)($_GET['id'] ?? 0);
            echo json_encode((new NotificationController())->acknowledge($id));
            break;

        case 'notification_bulk_action':
            header('Content-Type: application/json');
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode((new NotificationController())->bulkAction($data ?? []));
            break;

        case 'preferences':
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new PreferencesController())->index();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;

        case 'preferences_save':
            header('Content-Type: application/json');
            echo json_encode((new PreferencesController())->save());
            break;

        case 'global_search':
            header('Content-Type: application/json');
            echo json_encode((new \Modules\Admin\GlobalSearchController())->search());
            break;
            
        case 'dashboard':
        default:
            if (!Session::get('user_id')) { header('Location: index.php?route=login'); exit; }
            $res = (new DashboardController())->index();
            $title = $res['title']; $view = $res['view']; $data = $res['data'];
            require __DIR__ . '/views/layouts/main.php';
            break;
    }
} catch (\Throwable $e) {
    $isDebug = Env::get('DEBUG', 'false') === 'true';
    $errorMessage = $isDebug ? $e->getMessage() : 'An unexpected system error occurred. Please contact the administrator.';
    
    // Log the actual error to system logs if possible
    try {
        if (Session::get('user_id')) {
            (new \Modules\Admin\AdminRepository())->logAudit('error', 'system', null, null, ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    } catch (\Throwable $logError) {
        error_log($e->getMessage());
    }

    if (str_ends_with($route, '_submit') || str_contains($route, '_store') || str_contains($route, '_update') || str_contains($route, '_delete') || str_contains($route, '_save') || str_contains($route, '_poll') || $route === 'global_search') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
    } else {
        echo "<div style='padding: 2rem; font-family: sans-serif; text-align: center;'>";
        echo "<h1 style='color: #ef4444;'>System Error</h1>";
        echo "<p style='color: #64748b;'>{$errorMessage}</p>";
        if ($isDebug) {
            echo "<pre style='text-align: left; background: #f1f5f9; padding: 1rem; border-radius: 0.5rem; overflow-x: auto;'>{$e->getTraceAsString()}</pre>";
        }
        echo "<a href='index.php' style='display: inline-block; margin-top: 1rem; padding: 0.5rem 1rem; background: #2563eb; color: white; text-decoration: none; border-radius: 0.5rem;'>Go to Dashboard</a>";
        echo "</div>";
    }
}
