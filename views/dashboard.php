<?php
/** @var array $data */
$stats = $data['stats'] ?? [
    'total_equipment' => 0,
    'active_tasks' => 0,
    'past_due_tasks' => 0,
    'network_nodes' => 0
];
$chartData = $data['chartData'] ?? ['equipment_status' => [], 'tasks_status' => []];
$recentTasks = $data['recentTasks'] ?? [];
$recentActivity = $data['recentActivity'] ?? [];

$role = \Core\Session::get('role', 'user');

$statusColors = [
    'In Use' => '#10b981',
    'Available' => '#3b82f6',
    'Under Repair' => '#f59e0b',
    'Retired' => '#64748b',
    'Lost/Stolen' => '#ef4444',
    'todo' => '#94a3b8',
    'doing' => '#3b82f6',
    'done' => '#10b981',
    'past_due' => '#f59e0b',
    'dropped' => '#ef4444',
    // Fallbacks
    'Todo' => '#94a3b8',
    'Doing' => '#3b82f6',
    'Done' => '#10b981',
    'Past Due' => '#f59e0b',
    'Dropped' => '#ef4444'
];

// Ensure chartData elements are objects in JS
$jsonChartData = json_encode([
    'equipment_status' => (object)($chartData['equipment_status'] ?? []),
    'tasks_status' => (object)($chartData['tasks_status'] ?? [])
]);
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js" onerror="scriptFallback('fallback/js/chart.min.js')"></script>

<script>
function dashboard(chartData) {
    return {
        init() {
            // Wait for Chart.js to be available (especially needed for fallback loading)
            const waitForChart = setInterval(() => {
                if (typeof Chart !== 'undefined') {
                    clearInterval(waitForChart);
                    this.initEquipmentChart();
                    this.initTaskChart();
                }
            }, 50);

            // Safety timeout (give up after 5 seconds)
            setTimeout(() => clearInterval(waitForChart), 5000);
        },
        initEquipmentChart() {
            const canvas = document.getElementById('equipmentChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            
            let data = chartData.equipment_status || {};
            
            // Fallback to dummy if empty
            if (Object.keys(data).length === 0) {
                data = { 'No Data': 1 };
            }
            
            const statusColors = <?php echo json_encode($statusColors); ?>;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(data),
                    datasets: [{
                        data: Object.values(data),
                        backgroundColor: Object.keys(data).map(k => statusColors[k] || '#e2e8f0'),
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: { size: 11, family: 'Inter', weight: 'bold' },
                                color: document.documentElement.classList.contains('dark') ? '#94a3b8' : '#64748b'
                            }
                        }
                    }
                }
            });
        },
        initTaskChart() {
            if (typeof Chart === 'undefined') return;
            const canvas = document.getElementById('taskChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            
            const taskData = chartData.tasks_status || {};
            
            // Expected statuses to ensure consistent order/labels
            const statuses = ['todo', 'doing', 'past_due', 'done', 'dropped'];
            const labels = ['To Do', 'Doing', 'Past Due', 'Done', 'Dropped'];
            
            const dataValues = statuses.map(s => taskData[s] || 0);
            const statusColors = <?php echo json_encode($statusColors); ?>;
            const colors = statuses.map(s => statusColors[s] || '#94a3b8');
            
            // If all values are 0, add a small hint or just render empty
            const totalTasks = dataValues.reduce((a, b) => a + b, 0);
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Tasks',
                        data: dataValues,
                        backgroundColor: colors,
                        borderRadius: 8,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { 
                                stepSize: 1, 
                                color: '#94a3b8',
                                display: totalTasks > 0
                            },
                            grid: { color: 'rgba(148, 163, 184, 0.1)' }
                        },
                        x: { 
                            ticks: { color: '#94a3b8', font: { size: 10, weight: 'bold' } },
                            grid: { display: false }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: totalTasks > 0
                        }
                    }
                }
            });
        }
    }
}
</script>

<div class="space-y-8" x-data="dashboard(<?php echo htmlspecialchars($jsonChartData); ?>)">
    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-2xl flex items-center justify-center text-xl">
                <i class="bi bi-pc-display"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Equipment</p>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white"><?php echo $stats['total_equipment']; ?></h3>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 rounded-2xl flex items-center justify-center text-xl">
                <i class="bi bi-list-check"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Active Tasks</p>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white"><?php echo $stats['active_tasks']; ?></h3>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 text-orange-600 rounded-2xl flex items-center justify-center text-xl">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Past Due</p>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white"><?php echo $stats['past_due_tasks']; ?></h3>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4 transition-all hover:shadow-md">
            <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/30 text-purple-600 rounded-2xl flex items-center justify-center text-xl">
                <i class="bi bi-diagram-3"></i>
            </div>
            <div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Network Nodes</p>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white"><?php echo $stats['network_nodes']; ?></h3>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Equipment Status Distribution</h4>
            <div class="h-64 flex items-center justify-center relative">
                <canvas id="equipmentChart"></canvas>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-900 p-8 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-6">Task Progress Overview</h4>
            <div class="h-64 flex items-center justify-center relative">
                <canvas id="taskChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Data -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Tasks -->
        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex flex-col">
            <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800 flex justify-between items-center">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Recent Tasks</h4>
                <a href="index.php?route=list_tasks" class="text-xs font-bold text-blue-600 hover:underline">View All</a>
            </div>
            <div class="flex-1 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 dark:bg-slate-800/50">
                            <th class="px-8 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Task Name</th>
                            <th class="px-8 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-8 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest">Due Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                        <?php foreach($recentTasks as $task): ?>
                            <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/30 transition-colors">
                                <td class="px-8 py-4">
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($task['title']); ?></p>
                                    <p class="text-[10px] text-slate-400">Assigned to: <?php echo htmlspecialchars($task['assignee_names'] ?? 'Unassigned'); ?></p>
                                </td>
                                <td class="px-8 py-4 text-xs">
                                    <span class="px-2.5 py-1 rounded-lg font-bold <?php 
                                        echo match(strtolower($task['status'])) {
                                            'done' => 'bg-emerald-100 text-emerald-700',
                                            'doing' => 'bg-blue-100 text-blue-700',
                                            'todo' => 'bg-slate-100 text-slate-600',
                                            default => 'bg-orange-100 text-orange-700'
                                        };
                                    ?>">
                                        <?php echo ucfirst($task['status']); ?>
                                    </span>
                                </td>
                                <td class="px-8 py-4 text-xs text-slate-500 font-medium">
                                    <?php echo $task['deadline'] ? date('d/m/Y', strtotime($task['deadline'])) : '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($recentTasks)): ?>
                            <tr><td colspan="3" class="px-8 py-8 text-center text-xs text-slate-400 italic">No tasks found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col">
            <div class="px-8 py-6 border-b border-slate-50 dark:border-slate-800">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">
                    <?php echo $role === 'admin' ? 'System Logs' : 'Recent Notifications'; ?>
                </h4>
            </div>
            <div class="p-6 space-y-6">
                <?php foreach($recentActivity as $activity): ?>
                    <div class="flex gap-4">
                        <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0 <?php 
                            echo match($activity['type'] ?? 'info') {
                                'error', 'urgent', 'high' => 'bg-red-500',
                                'warning' => 'bg-orange-500',
                                default => 'bg-blue-500'
                            };
                        ?>"></div>
                        <div>
                            <p class="text-xs text-slate-700 dark:text-slate-300 font-medium leading-relaxed">
                                <?php echo htmlspecialchars($activity['message']); ?>
                            </p>
                            <p class="text-[10px] text-slate-400 mt-1" x-text="Alpine.store('app').timeAgo('<?php echo $activity['created_at']; ?>')"></p>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if(empty($recentActivity)): ?>
                    <p class="text-xs text-slate-400 italic text-center py-8">No recent activity.</p>
                <?php endif; ?>
            </div>
            <div class="mt-auto p-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 text-center">
                <a href="index.php?route=<?php echo $role === 'admin' ? 'admin_logs' : 'list_notifications'; ?>" class="text-xs font-bold text-slate-500 hover:text-blue-600 transition-colors">
                    View Complete History <i class="bi bi-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>
