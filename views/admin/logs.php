<?php
/** @var array $data */
$logs = $data['logs'];
$totalPages = $data['pages'];
$currentPage = $data['currentPage'];
$filters = $data['filters'];

$levelColors = [
    'info' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'error' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    'critical' => 'bg-red-600 text-white dark:bg-red-600 dark:text-white'
];

$categories = ['auth', 'equipment', 'task', 'network', 'user', 'system', 'security', 'tool'];

$sortBy = $filters['sort_by'] ?? 'timestamp';
$sortDir = $filters['sort_dir'] ?? 'DESC';

function sortUrl($field, $filters) {
    $dir = ($field === ($filters['sort_by'] ?? 'timestamp') && ($filters['sort_dir'] ?? 'DESC') === 'ASC') ? 'DESC' : 'ASC';
    $f = $filters;
    $f['sort_by'] = $field;
    $f['sort_dir'] = $dir;
    $f['page'] = 1; // Reset to page 1 on sort
    return "index.php?route=admin_logs&" . http_build_query(array_filter($f));
}

// Build query string for pagination
$queryString = http_build_query(array_filter($filters));
?>

<div class="max-w-7xl mx-auto space-y-6" x-data="logManager()">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">System Logs</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Audit trail of all system events and user actions.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php?route=admin_logs&export=csv&<?php echo $queryString; ?>" class="px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 transition-all flex items-center">
                <i class="bi bi-file-earmark-spreadsheet mr-2"></i> Export CSV
            </a>
            <a href="index.php?route=admin_logs&export=txt&<?php echo $queryString; ?>" class="px-5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 transition-all flex items-center">
                <i class="bi bi-file-earmark-text mr-2"></i> Export TXT
            </a>
            <button @click="clearLogs" class="px-5 py-2.5 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-bold hover:bg-rose-100 transition-all flex items-center">
                <i class="bi bi-trash3 mr-2"></i> Clear Logs
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm transition-colors">
        <form action="index.php" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <input type="hidden" name="route" value="admin_logs">
            <input type="hidden" name="sort_by" value="<?php echo htmlspecialchars($sortBy); ?>">
            <input type="hidden" name="sort_dir" value="<?php echo htmlspecialchars($sortDir); ?>">
            
            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Date Range</label>
                <div class="flex gap-2">
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($filters['date_from'] ?? ''); ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($filters['date_to'] ?? ''); ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Level</label>
                <select name="level" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Levels</option>
                    <option value="info" <?php echo $filters['level'] === 'info' ? 'selected' : ''; ?>>Info</option>
                    <option value="warning" <?php echo $filters['level'] === 'warning' ? 'selected' : ''; ?>>Warning</option>
                    <option value="error" <?php echo $filters['level'] === 'error' ? 'selected' : ''; ?>>Error</option>
                    <option value="critical" <?php echo $filters['level'] === 'critical' ? 'selected' : ''; ?>>Critical</option>
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Category</label>
                <select name="category" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat; ?>" <?php echo $filters['category'] === $cat ? 'selected' : ''; ?>><?php echo ucfirst($cat); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-end gap-2 col-span-1 md:col-span-2 lg:col-span-1">
                <button type="submit" class="flex-1 px-5 py-2.5 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition-all">Apply Filters</button>
                <a href="index.php?route=admin_logs" class="px-5 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl text-xs font-bold hover:bg-slate-200 transition-all flex items-center justify-center">Reset</a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-6 py-4">
                            <a href="<?php echo sortUrl('timestamp', $filters); ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 flex items-center gap-1 transition-colors">
                                Timestamp
                                <?php if($sortBy === 'timestamp') echo $sortDir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-4">
                            <a href="<?php echo sortUrl('level', $filters); ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 flex items-center gap-1 transition-colors">
                                Level
                                <?php if($sortBy === 'level') echo $sortDir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-4">
                            <a href="<?php echo sortUrl('category', $filters); ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 flex items-center gap-1 transition-colors">
                                Category
                                <?php if($sortBy === 'category') echo $sortDir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-4">
                            <a href="<?php echo sortUrl('username', $filters); ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 flex items-center gap-1 transition-colors">
                                User / IP
                                <?php if($sortBy === 'username') echo $sortDir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50/30 dark:hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4 text-xs text-slate-500 whitespace-nowrap">
                                <?php echo date('d/m/Y, h:i A', strtotime($log['timestamp'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-wider <?php echo $levelColors[$log['level']] ?? ''; ?>">
                                    <?php echo $log['level']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo htmlspecialchars($log['category']); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></span>
                                    <span class="text-[10px] text-slate-400 font-mono"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600 dark:text-slate-400">
                                <?php echo htmlspecialchars($log['message']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($logs)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-12 h-12 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-300">
                                        <i class="bi bi-info-circle text-2xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm italic">No logs found matching your criteria.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex items-center justify-between px-6 py-4 transition-colors">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing page <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $currentPage; ?></span> of <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $totalPages; ?></span> (<?php echo $data['total']; ?> entries)
        </p>
        <div class="flex space-x-1">
            <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <a href="index.php?route=admin_logs&page=<?php echo $i; ?>&<?php echo $queryString; ?>" 
                   class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all <?php echo $i === $currentPage ? 'bg-blue-600 text-white shadow-md' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
function logManager() {
    return {
        clearLogs() {
            Alpine.store('app').confirm('Clear System Logs', 'Are you sure you want to permanently delete ALL logs? This action is logged and cannot be undone.', async () => {
                try {
                    const response = await fetch('index.php?route=admin_clear_logs');
                    const result = await response.json();
                    if (result.success) {
                        Alpine.store('app').addToast('Success', 'Logs cleared successfully.', 'success');
                        setTimeout(() => window.location.reload(), 1000);
                    }
                } catch (e) {
                    Alpine.store('app').addToast('Error', 'Failed to clear logs.', 'error');
                }
            });
        }
    }
}
</script>
