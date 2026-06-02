<?php

declare(strict_types=1);

namespace Modules\Network;

use Core\Session;
use Exception;

class NetworkController
{
    private NetworkRepository $networkRepository;

    public function __construct()
    {
        $this->networkRepository = new NetworkRepository();
    }

    /**
     * List network records.
     */
    public function list(): array
    {
        if (!Session::get('user_id')) {
            throw new Exception("Unauthorized.");
        }

        $page = (int)($_GET['page'] ?? 1);
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortDir = $_GET['sort_dir'] ?? 'DESC';
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];

        $settingsRepo = new \Modules\Admin\SettingsRepository();
        $limit = (int)$settingsRepo->get('records_per_page', 20);

        $result = $this->networkRepository->getNetworks($page, $limit, $sortBy, $sortDir, $filters);
        $unassignedEquip = $this->networkRepository->getUnassignedEquipment();

        return [
            'title' => 'Network Infrastructure',
            'view' => 'views/network/list.php',
            'data' => [
                'networks' => $result['networks'],
                'total' => $result['total'],
                'pages' => $result['pages'],
                'current_page' => $page,
                'filters' => $filters,
                'unassigned_equipment' => $unassignedEquip,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir
            ]
        ];
    }

    /**
     * View a single network node.
     */
    public function view(int $id): array
    {
        if (!Session::get('user_id')) {
            throw new Exception("Unauthorized.");
        }

        $node = $this->networkRepository->getNetworkById($id);
        if (!$node) throw new Exception("Network node not found.");

        return [
            'title' => 'Network Node: ' . $node['ip_address'],
            'view' => 'views/network/view.php',
            'data' => [
                'node' => $node
            ]
        ];
    }

    /**
     * Store/Update network logic.
     */
    public function store(array $data): array
    {
        if (!Session::get('user_id')) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $id = (int)($data['network_id'] ?? 0);
            
            // Validation
            if (empty($data['ip_address'])) {
                return ['success' => false, 'message' => 'IP Address is required.'];
            }

            if (!filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
                return ['success' => false, 'message' => 'Invalid IP address format.'];
            }

            if ($id) {
                $this->networkRepository->updateNetwork($id, $data);
                (new \Modules\Admin\AdminRepository())->logEvent('info', 'network', "Network node updated: " . $data['ip_address'], ['node_id' => $id]);
                $msg = 'Network info updated.';
            } else {
                $newId = $this->networkRepository->createNetwork($data);
                (new \Modules\Admin\AdminRepository())->logEvent('info', 'network', "New network node created: " . $data['ip_address'], ['node_id' => $newId]);
                $msg = 'Network info created.';
            }

            return ['success' => true, 'message' => $msg, 'redirect' => 'index.php?route=list_network'];
        } catch (\PDOException $e) {
            // Check for duplicate entry error (SQLSTATE 23000)
            if ($e->getCode() === '23000' || str_contains($e->getMessage(), '1062')) {
                return ['success' => false, 'message' => "The IP Address \"{$data['ip_address']}\" is already registered. Each node must have a unique IP."];
            }
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete network record.
     */
    public function delete(int $id): array
    {
        if (!Session::get('user_id')) {
            return ['success' => false, 'message' => 'Unauthorized.'];
        }

        try {
            $this->networkRepository->deleteNetwork($id);
            (new \Modules\Admin\AdminRepository())->logEvent('info', 'network', "Network node deleted (ID: $id)");
            return ['success' => true, 'message' => 'Network record deleted.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Export to CSV.
     */
    public function export(): void
    {
        $result = $this->networkRepository->getNetworks(1, 5000, 'ip_address', 'ASC');
        $networks = $result['networks'];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="network_export_' . date('Ymd_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            'IP address', 
            'Cable no', 
            'Patch panel no', 
            'Patch panel port', 
            'Patch panel location', 
            'Switch no', 
            'Switch port', 
            'Switch location', 
            'Remarks', 
            'Created'
        ]);

        foreach ($networks as $n) {
            fputcsv($output, [
                $n['ip_address'] ?? '-',
                $n['cable_no'] ?? '-',
                $n['patch_panel_no'] ?? '-',
                $n['patch_panel_port'] ?? '-',
                $n['patch_panel_location'] ?? '-',
                $n['switch_no'] ?? '-',
                $n['switch_port'] ?? '-',
                $n['switch_location'] ?? '-',
                $n['remarks'] ?? '-',
                isset($n['created_at']) ? date('d/m/Y H:i', strtotime($n['created_at'])) : '-'
            ]);
        }
        fclose($output);
        exit;
    }
}
