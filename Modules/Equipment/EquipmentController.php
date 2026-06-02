<?php

declare(strict_types=1);

namespace Modules\Equipment;

use Core\Session;
use Exception;

class EquipmentController
{
    private EquipmentRepository $equipmentRepository;
    private EquipmentBlockRepository $blockRepository;

    public function __construct()
    {
        $this->equipmentRepository = new EquipmentRepository();
        $this->blockRepository = new EquipmentBlockRepository();
    }

    /**
     * Manage Predefined Blocks (Admin only).
     */
    public function blocks(): array
    {
        if (Session::get('role') !== 'admin') {
            throw new Exception("Access denied.");
        }

        return [
            'title' => 'Manage Predefined Blocks',
            'view' => 'views/equipment/blocks.php',
            'data' => [
                'blocks' => $this->blockRepository->getAll()
            ]
        ];
    }

    /**
     * Save predefined block (AJAX).
     */
    public function saveBlock(array $data): array
    {
        if (Session::get('role') !== 'admin') {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $this->blockRepository->save($data);
            return ['success' => true, 'message' => 'Predefined block saved.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete predefined block (AJAX).
     */
    public function deleteBlock(int $id): array
    {
        if (Session::get('role') !== 'admin') {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        try {
            if ($this->blockRepository->isInUse($id)) {
                return [
                    'success' => false, 
                    'message' => 'Cannot delete block: It is currently attached to one or more Equipment Types. Please detach it first.'
                ];
            }

            $this->blockRepository->delete($id);
            return ['success' => true, 'message' => 'Block deleted.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * List equipment inventory.
     */
    public function list(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $filters = [
            'type_id' => $_GET['type_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $settingsRepo = new \Modules\Admin\SettingsRepository();
        $limit = max(1, (int)$settingsRepo->get('records_per_page', 20));

        $res = $this->equipmentRepository->getEquipments($filters, $page, $limit);

        // If AJAX request, return only the partial table body
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: text/html');
            $data = [
                'equipments' => $res['items'],
                'filters' => $filters,
                'pages' => (int)$res['pages'],
                'currentPage' => (int)$page,
                'total' => (int)$res['total']
            ];
            require __DIR__ . '/../../views/equipment/partial_list.php';
            exit;
        }

        return [
            'title' => 'Equipment Inventory',
            'view' => 'views/equipment/list.php',
            'data' => [
                'equipments' => $res['items'],
                'total' => (int)$res['total'],
                'pages' => (int)$res['pages'],
                'currentPage' => (int)$page,
                'filters' => $filters,
                'types' => $this->equipmentRepository->getTypes()
            ]
        ];
    }

    /**
     * Show add equipment form.
     */
    public function add(): array
    {
        return [
            'title' => 'Add Equipment',
            'view' => 'views/equipment/add_edit.php',
            'data' => [
                'equipment' => null,
                'isEdit' => false,
                'types' => $this->equipmentRepository->getTypes(),
                'blocks' => $this->blockRepository->getAll()
            ]
        ];
    }

    /**
     * Show edit equipment form.
     */
    public function edit(int $id): array
    {
        $equipment = $this->equipmentRepository->getEquipmentById($id);
        if (!$equipment) throw new Exception("Equipment not found.");

        return [
            'title' => 'Edit Equipment: ' . $equipment['serial_number'],
            'view' => 'views/equipment/add_edit.php',
            'data' => [
                'equipment' => $equipment,
                'isEdit' => true,
                'types' => $this->equipmentRepository->getTypes(),
                'blocks' => $this->blockRepository->getAll()
            ]
        ];
    }

    /**
     * Save equipment (AJAX/FormData).
     */
    public function save(array $data): array
    {
        // For FormData, values like 'network' and 'custom_data' come as JSON strings
        if (is_string($data['network'] ?? null)) {
            $data['network'] = json_decode($data['network'], true);
        }
        if (is_string($data['custom_data'] ?? null)) {
            $data['custom_data'] = json_decode($data['custom_data'], true);
        }

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $id = !empty($data['id']) ? (int)$data['id'] : null;
            $existingEquipment = $id ? $this->equipmentRepository->getEquipmentById($id) : null;
            $currentImages = $existingEquipment ? (json_decode($existingEquipment['images'] ?? '[]', true) ?: []) : [];

            $settingsRepo = new \Modules\Admin\SettingsRepository();

            // Handle Image Deletions
            if (!empty($data['images_to_delete'])) {
                $toDelete = is_string($data['images_to_delete']) ? json_decode($data['images_to_delete'], true) : $data['images_to_delete'];
                foreach ($toDelete as $img) {
                    if (($key = array_search($img, $currentImages)) !== false) {
                        unset($currentImages[$key]);
                        if (file_exists($img)) @unlink($img);
                    }
                }
                $currentImages = array_values($currentImages);
            }

            // 1. Validation: Unique Serial Number (Except N/A / NULL)
            if (!empty($data['serial_number']) && strtoupper($data['serial_number']) !== 'N/A') {
                $stmt = \Core\Database::getInstance()->prepare("SELECT id FROM equipments WHERE serial_number = ? AND id != ?");
                $stmt->execute([$data['serial_number'], $id ?? 0]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => "Serial Number \"{$data['serial_number']}\" is already registered."];
                }
            }

            // 2. Validation: Unique MAC Address (Except N/A / NULL)
            if (!empty($data['mac_address']) && strtoupper($data['mac_address']) !== 'N/A') {
                $stmt = \Core\Database::getInstance()->prepare("SELECT id FROM equipments WHERE mac_address = ? AND id != ?");
                $stmt->execute([$data['mac_address'], $id ?? 0]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => "MAC Address \"{$data['mac_address']}\" is already registered."];
                }
            }

            // Handle Warranty File Upload
            if (isset($_FILES['warranty_document']) && $_FILES['warranty_document']['error'] === UPLOAD_ERR_OK) {
                $upload = $_FILES['warranty_document'];
                $maxSizeMB = (int)$settingsRepo->get('warranty_max_upload_size', '10');
                $allowedExtStr = $settingsRepo->get('warranty_allowed_extensions', 'pdf,jpg,png,jpeg');
                $allowedExtensions = array_map('trim', explode(',', strtolower($allowedExtStr)));
                
                $ext = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
                
                if (!in_array($ext, $allowedExtensions)) {
                    return ['success' => false, 'message' => 'Warranty format not allowed. Allowed: ' . strtoupper($allowedExtStr)];
                }
                
                if ($upload['size'] > $maxSizeMB * 1024 * 1024) {
                    return ['success' => false, 'message' => "Warranty file too large (Max {$maxSizeMB}MB)."];
                }

                $targetDir = 'storage/uploads/warranty/';
                if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
                
                $fileName = 'warranty_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($upload['tmp_name'], $targetDir . $fileName)) {
                    $data['warranty_file'] = $targetDir . $fileName;
                }
            }

            // Handle Equipment Photos (Gallery)
            if (!empty($_FILES['equipment_photos'])) {
                $photoDir = 'storage/uploads/equipment/';
                if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);

                $maxSizeMB = (int)$settingsRepo->get('equipment_max_upload_size', '5');
                $allowedExtStr = $settingsRepo->get('equipment_allowed_extensions', 'jpg,png,jpeg');
                $allowedExtensions = array_map('trim', explode(',', strtolower($allowedExtStr)));

                $files = $_FILES['equipment_photos'];
                $newlyUploadedCount = 0;
                
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        if (count($currentImages) >= 3) break;

                        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                        
                        if (in_array($ext, $allowedExtensions)) {
                            if ($files['size'][$i] <= $maxSizeMB * 1024 * 1024) {
                                $fileName = 'equip_' . time() . '_' . uniqid() . '.' . $ext;
                                if (move_uploaded_file($files['tmp_name'][$i], $photoDir . $fileName)) {
                                    $currentImages[] = $photoDir . $fileName;
                                }
                            }
                        }
                    }
                }
            }
            $data['images'] = array_slice($currentImages, 0, 3); // Final safety cap

            $networkData = null;
            if (!empty($data['include_network']) && $data['include_network'] !== 'false' && !empty($data['network']['ip_address'])) {
                $networkData = $data['network'];
            }

            $savedId = $this->equipmentRepository->saveEquipment($data, $networkData);
            $eventAction = $id ? 'updated' : 'created';
            (new \Modules\Admin\AdminRepository())->logEvent('info', 'equipment', "Asset $eventAction: " . ($data['brand'] . ' ' . $data['model']), ['asset_id' => $id ?: $savedId]);
            return ['success' => true, 'message' => 'Equipment saved successfully.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * View equipment details.
     */
    public function view(int $id): array
    {
        $equipment = $this->equipmentRepository->getEquipmentById($id);
        if (!$equipment) throw new Exception("Equipment not found.");

        return [
            'title' => 'Equipment Details',
            'view' => 'views/equipment/view.php',
            'data' => [
                'equipment' => $equipment
            ]
        ];
    }

    /**
     * Delete equipment (AJAX).
     */
    public function delete(int $id): array
    {
        try {
            $this->equipmentRepository->deleteEquipment($id);
            return ['success' => true, 'message' => 'Equipment deleted.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Bulk delete equipment (AJAX).
     */
    public function bulkDelete(array $data): array
    {
        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $ids = $data['ids'] ?? [];
            if (empty($ids)) throw new Exception("No items selected.");

            $count = $this->equipmentRepository->bulkDelete($ids);
            return ['success' => true, 'message' => "$count items deleted."];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get data for QR code generation (AJAX).
     */
    public function getQrData(string $ids): array
    {
        if (empty($ids)) return [];
        $idArray = array_map('intval', explode(',', $ids));
        
        $results = [];
        foreach ($idArray as $id) {
            $e = $this->equipmentRepository->getEquipmentById($id);
            if ($e) {
                $results[] = [
                    'id' => $e['id'],
                    'name' => $e['name'],
                    'brand' => $e['brand'],
                    'model' => $e['model'],
                    'serial_number' => $e['serial_number'],
                    'mac_address' => $e['mac_address'],
                    'ip_address' => $e['network']['ip_address'] ?? null,
                    'location' => $e['office_location'] . ($e['floor'] ? " ({$e['floor']})" : ""),
                    'department' => $e['department'],
                    'status' => $e['status']
                ];
            }
        }
        
        return $results;
    }

    /**
     * Manage Equipment Types (Admin only).
     */
    public function types(): array
    {
        if (Session::get('role') !== 'admin') {
            throw new Exception("Access denied.");
        }

        return [
            'title' => 'Manage Equipment Types',
            'view' => 'views/equipment/types.php',
            'data' => [
                'types' => $this->equipmentRepository->getTypes(),
                'blocks' => $this->blockRepository->getAll()
            ]
        ];
    }

    /**
     * Save equipment type (AJAX).
     */
    public function saveType(array $data): array
    {
        $adminRepo = new \Modules\Admin\AdminRepository();

        if (Session::get('role') !== 'admin') {
            $adminRepo->logEvent('warning', 'security', 'Unauthorized attempt to save equipment type by user: ' . Session::get('username'));
            return ['success' => false, 'message' => 'Access denied. Only administrators can manage equipment types.'];
        }

        if (!Session::validateCsrfToken($data['csrf_token'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid security token.'];
        }

        try {
            $this->equipmentRepository->saveType($data);
            (new \Modules\Admin\AdminRepository())->logEvent('info', 'equipment', 'Equipment type saved: ' . ($data['name'] ?? 'Unknown'));
            return ['success' => true, 'message' => 'Equipment type saved.'];
        } catch (\Throwable $e) {
            $adminRepo->logEvent('error', 'equipment', 'Failed to save equipment type: ' . $e->getMessage(), $data);
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete equipment type (AJAX).
     */
    public function deleteType(int $id): array
    {
        if (Session::get('role') !== 'admin') {
            return ['success' => false, 'message' => 'Access denied.'];
        }

        try {
            if ($this->equipmentRepository->isTypeInUse($id)) {
                return [
                    'success' => false, 
                    'message' => 'Cannot delete type: There are equipment assets assigned to this type. Please delete or reassign them first.'
                ];
            }

            $this->equipmentRepository->deleteType($id);
            return ['success' => true, 'message' => 'Equipment type deleted.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Export equipment data to CSV.
     */
    public function export(): void
    {
        $typeId = !empty($_GET['type_id']) ? (int)$_GET['type_id'] : null;
        $ids = !empty($_GET['ids']) ? explode(',', $_GET['ids']) : [];

        if (!$typeId && empty($ids)) {
            die("Error: Please select a category or specific items to export.");
        }

        // If specific IDs are provided, we need to determine the type if not set
        if (!empty($ids) && !$typeId) {
            $firstId = (int)$ids[0];
            $firstEq = $this->equipmentRepository->getEquipmentById($firstId);
            if ($firstEq) $typeId = (int)$firstEq['type_id'];
        }

        if (!$typeId) {
            die("Error: Equipment category is required for export to ensure consistent columns.");
        }

        $type = $this->equipmentRepository->getTypeById($typeId);
        if (!$type) die("Error: Invalid category.");

        // Get equipment items
        $filters = ['type_id' => $typeId];
        // If specific IDs selected, we'll filter by them later or use a different repo method
        // For simplicity, let's fetch all and filter in PHP if IDs are present
        $res = $this->equipmentRepository->getEquipments($filters, 1, 10000); // High limit for export
        $items = $res['items'];

        if (!empty($ids)) {
            $items = array_filter($items, fn($e) => in_array($e['id'], $ids));
        }

        if (empty($items)) die("Error: No data to export.");

        // 1. Type/Category
        $headers = ['Type/Category'];
        
        // 2. Identification
        $headers = array_merge($headers, ['Label', 'Brand', 'Model', 'Serial Number']);
        
        // 3. Specifications (Dynamic)
        $schema = json_decode($type['form_schema'] ?? '[]', true);
        $customColNames = [];
        foreach ($schema as $field) {
            $headers[] = $field['label'];
            $customColNames[] = $field['name'];
        }

        // 4. Network Connection
        $headers = array_merge($headers, ['MAC Address', 'IP Address']);

        // 5. Warranty Info
        $headers = array_merge($headers, ['Warranty Seller', 'Purchase Date', 'Expiry Date']);

        // 6. Status & Condition
        $headers = array_merge($headers, ['Status', 'Condition']);

        // 7. Location & Allocated
        $headers = array_merge($headers, ['Office Location', 'Floor', 'Department / Room', 'Assigned To']);

        // 8. Meta
        $headers[] = 'Date Added';

        // Generate CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="Equipment_Export_' . str_replace(' ', '_', $type['name']) . '_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);

        foreach ($items as $e) {
            $customData = json_decode($e['custom_data'] ?? '[]', true);
            
            // Start Row with Type/Category
            $row = [$e['type_name']];

            // Identification
            $row[] = $e['name'];
            $row[] = $e['brand'] ?? '';
            $row[] = $e['model'] ?? '';
            $row[] = $e['serial_number'] ?? '';

            // Specifications (Dynamic)
            foreach ($customColNames as $colName) {
                $val = $customData[$colName] ?? '';
                $row[] = is_array($val) ? implode(', ', $val) : $val;
            }

            // Network Connection
            $row[] = $e['mac_address'] ?? '';
            $row[] = $e['ip_address'] ?? '';

            // Warranty Info
            $row[] = $e['warranty_seller'] ?? '';
            $row[] = $e['warranty_purchase_date'] ?? '';
            $row[] = $e['warranty_expiry'] ?? '';

            // Status & Condition
            $row[] = $e['status'];
            $row[] = ucfirst($e['condition'] ?? '');

            // Location & Allocated
            $row[] = $e['office_location'] ?? '';
            $row[] = $e['floor'] ?? '';
            $row[] = $e['department'] ?? '';
            $row[] = $e['assigned_to'] ?? '';

            // Meta
            $row[] = $e['created_at'];

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
