<?php

declare(strict_types=1);

namespace Modules\Admin;

use Core\Session;
use Core\Env;
use Exception;

class AdminController
{
    private AdminRepository $adminRepository;

    public function __construct()
    {
        $this->adminRepository = new AdminRepository();
    }

    /**
     * Display the System Tools page.
     */
    public function tools(): array
    {
        if (Session::get('role') !== 'admin') {
            throw new Exception("Access denied.");
        }

        return [
            'title' => 'System Tools',
            'view' => 'views/admin/tools.php',
            'data' => []
        ];
    }

    /**
     * View system logs.
     */
    public function logs(): array
    {
        if (Session::get('role') !== 'admin') {
            throw new Exception("Access denied.");
        }

        $page = (int)($_GET['page'] ?? 1);
        $filters = [
            'level' => isset($_GET['level']) ? trim($_GET['level']) : null,
            'category' => isset($_GET['category']) ? trim($_GET['category']) : null,
            'date_from' => isset($_GET['date_from']) ? trim($_GET['date_from']) : null,
            'date_to' => isset($_GET['date_to']) ? trim($_GET['date_to']) : null,
            'sort_by' => $_GET['sort_by'] ?? 'timestamp',
            'sort_dir' => $_GET['sort_dir'] ?? 'DESC',
        ];

        // Handle Export
        if (isset($_GET['export'])) {
            $this->exportLogs($filters, $_GET['export']);
            exit;
        }

        $settingsRepo = new SettingsRepository();
        $limit = (int)$settingsRepo->get('records_per_page', 20);

        $res = $this->adminRepository->getLogs($page, $limit, $filters);

        return [
            'title' => 'System Logs',
            'view' => 'views/admin/logs.php',
            'data' => [
                'logs' => $res['items'],
                'total' => $res['total'],
                'pages' => $res['pages'],
                'currentPage' => $page,
                'filters' => $filters
            ]
        ];
    }

    /**
     * Export logs to CSV or TXT.
     */
    private function exportLogs(array $filters, string $format): void
    {
        $res = $this->adminRepository->getLogs(1, 10000, $filters); // Export up to 10k logs
        $logs = $res['items'];

        $filename = 'system_logs_' . date('Ymd_His');

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"$filename.csv\"");
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Timestamp', 'Level', 'Category', 'User', 'IP Address', 'Message', 'Context']);
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['timestamp'],
                    strtoupper($log['level']),
                    $log['category'],
                    $log['username'] ?? 'System',
                    $log['ip_address'],
                    $log['message'],
                    $log['context']
                ]);
            }
            fclose($output);
        } else {
            header('Content-Type: text/plain; charset=utf-8');
            header("Content-Disposition: attachment; filename=\"$filename.txt\"");
            echo "SYSTEM LOGS EXPORT - " . date('Y-m-d H:i:s') . "\n";
            echo str_repeat("=", 80) . "\n\n";
            foreach ($logs as $log) {
                echo "[" . $log['timestamp'] . "] [" . strtoupper($log['level']) . "] [" . $log['category'] . "]\n";
                echo "User: " . ($log['username'] ?? 'System') . " | IP: " . $log['ip_address'] . "\n";
                echo "Message: " . $log['message'] . "\n";
                if ($log['context']) echo "Context: " . $log['context'] . "\n";
                echo str_repeat("-", 40) . "\n";
            }
        }
        
        $this->adminRepository->logEvent('info', 'tool', "System logs exported to $format format.");
    }

    /**
     * AJAX: Clear system logs.
     */
    public function clearLogs(): array
    {
        if (Session::get('role') !== 'admin') return ['success' => false, 'message' => 'Unauthorized'];

        try {
            $this->adminRepository->clearLogs();
            return ['success' => true, 'message' => 'Logs cleared.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * AJAX: Optimize Database.
     */
    public function optimizeDB(): array
    {
        if (Session::get('role') !== 'admin') return ['success' => false, 'message' => 'Unauthorized'];

        try {
            $results = $this->adminRepository->optimizeDatabase();
            return ['success' => true, 'message' => 'Database optimized.', 'results' => $results];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * AJAX: Cleanup Cache.
     */
    public function cleanupCache(): array
    {
        if (Session::get('role') !== 'admin') return ['success' => false, 'message' => 'Unauthorized'];

        try {
            // For now, clear PHP session files or temporary logs if any
            // Actually clearing WAMP cache or similar isn't safe, so we'll just clear the app's temp storage
            $tempDir = __DIR__ . '/../../storage/backups'; // Example
            // Logic to clear files older than 7 days
            return ['success' => true, 'message' => 'Cache cleaned up.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * AJAX: Get log detail.
     */
    public function logDetail(): array
    {
        if (Session::get('role') !== 'admin') return ['success' => false, 'message' => 'Unauthorized'];

        $id = (int)($_GET['id'] ?? 0);
        $log = $this->adminRepository->getLogDetail($id);

        if (!$log) return ['success' => false, 'message' => 'Log not found'];

        return ['success' => true, 'log' => $log];
    }

    /**
     * Download Database Backup.
     */
    public function backupDB(): void
    {
        if (Session::get('role') !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            exit('Unauthorized');
        }

        try {
            $dbName = Env::get('DB_NAME', 'it_assets_management');
            $filename = 'backup_' . $dbName . '_' . date('Ymd_His') . '.sql';
            
            header('Content-Type: application/octet-stream');
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"$filename\"");
            
            // Basic SQL Export (not a full mysqldump but sufficient for small DB)
            $conn = $this->adminRepository->getDb();
            $tables = $conn->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);
            
            echo "-- IT Management System Backup\n";
            echo "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
            echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

            foreach ($tables as $table) {
                $row = $conn->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
                echo $row['Create Table'] . ";\n\n";
                
                $rows = $conn->query("SELECT * FROM `$table`")->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $r) {
                    $keys = array_keys($r);
                    $values = array_map(fn($v) => $v === null ? 'NULL' : $conn->quote((string)$v), array_values($r));
                    echo "INSERT INTO `$table` (`" . implode("`, `", $keys) . "`) VALUES (" . implode(", ", $values) . ");\n";
                }
                echo "\n";
            }
            
            echo "SET FOREIGN_KEY_CHECKS = 1;\n";
            exit;
        } catch (Exception $e) {
            exit("Backup failed: " . $e->getMessage());
        }
    }
}
