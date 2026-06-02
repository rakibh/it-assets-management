<?php

declare(strict_types=1);

namespace Modules\Admin;

use Exception;

class GlobalSearchController
{
    private GlobalSearchRepository $globalSearchRepository;

    public function __construct()
    {
        $this->globalSearchRepository = new GlobalSearchRepository();
    }

    public function search(): array
    {
        if (!\Core\Session::get('user_id')) {
            return ['success' => false, 'message' => 'Unauthorized'];
        }

        try {
            $query = trim($_GET['q'] ?? '');
            if (strlen($query) < 2) {
                return ['success' => true, 'results' => []];
            }

            $results = $this->globalSearchRepository->search($query);
            return ['success' => true, 'results' => $results];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
