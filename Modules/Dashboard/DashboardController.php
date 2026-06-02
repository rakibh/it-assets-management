<?php

declare(strict_types=1);

namespace Modules\Dashboard;

use Core\Session;

class DashboardController
{
    private DashboardRepository $dashboardRepository;

    public function __construct()
    {
        $this->dashboardRepository = new DashboardRepository();
    }

    /**
     * Display the dashboard with real data.
     */
    public function index(): array
    {
        $userId = (int)Session::get('user_id');
        $role = Session::get('role', 'user');

        if (!$userId) {
            header('Location: index.php?route=login');
            exit;
        }

        return [
            'title' => 'Dashboard',
            'view' => 'views/dashboard.php',
            'data' => [
                'stats' => $this->dashboardRepository->getStats($userId, $role),
                'chartData' => $this->dashboardRepository->getChartData(),
                'recentTasks' => $this->dashboardRepository->getRecentTasks($userId, $role),
                'recentActivity' => $this->dashboardRepository->getRecentActivity($userId, $role)
            ]
        ];
    }
}
