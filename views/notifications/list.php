<?php
/** @var array $data */
$notifications = $data['notifications'];
$totalPages = $data['pages'];
$currentPage = $data['currentPage'];
$filters = $data['filters'] ?? ['status' => '', 'date_from' => '', 'date_to' => ''];

$priorityColors = [
    'info' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
    'error' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    'critical' => 'bg-red-600 text-white'
];

$typeIcons = [
    'task' => 'bi-list-check',
    'task_assignment' => 'bi-person-plus',
    'task_status' => 'bi-arrow-left-right',
    'equipment' => 'bi-cpu',
    'network' => 'bi-diagram-3',
    'user' => 'bi-people',
    'warranty' => 'bi-shield-check',
    'system' => 'bi-gear'
];
?>

<div class="max-w-6xl mx-auto space-y-6" x-data="notificationManager()">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Notifications</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Stay updated with system activities and task assignments.</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="markSelectedAsRead" :disabled="selectedIds.length === 0" 
                    class="px-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-50 transition-all disabled:opacity-50">
                Mark Read
            </button>
            <button @click="archiveSelected" :disabled="selectedIds.length === 0" 
                    class="px-4 py-2 bg-rose-50 dark:bg-rose-900/20 text-rose-600 dark:text-rose-400 rounded-xl text-xs font-bold hover:bg-rose-100 transition-all disabled:opacity-50">
                Archive
            </button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm flex flex-wrap items-center gap-4">
        <select x-model="filters.status" @change="applyFilters(1)" class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500 dark:text-slate-200">
            <option value="">All Notifications</option>
            <option value="unread">Unread Only</option>
            <option value="read">Read Only</option>
            <option value="archived">Archived</option>
        </select>
        
        <div class="flex items-center gap-2">
            <input type="date" x-model="filters.date_from" @change="applyFilters(1)" class="px-3 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500 dark:text-slate-200">
            <span class="text-slate-400 text-xs font-bold">to</span>
            <input type="date" x-model="filters.date_to" @change="applyFilters(1)" class="px-3 py-2 bg-slate-50 dark:bg-slate-800 border-none rounded-xl text-xs font-bold focus:ring-2 focus:ring-blue-500 dark:text-slate-200">
        </div>

        <button @click="resetFilters" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">Reset</button>
    </div>

    <!-- Notifications List -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="pl-6 py-4 w-10">
                            <input type="checkbox" @change="toggleAll" :checked="isAllSelected" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Event</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <?php foreach ($notifications as $n): ?>
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-colors group <?php echo !$n['is_read'] ? 'bg-blue-50/30 dark:bg-blue-900/5' : ''; ?>">
                            <td class="pl-6 py-4">
                                <input type="checkbox" value="<?php echo $n['id']; ?>" x-model="selectedIds" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4" @click="goToNotification(<?php echo htmlspecialchars(json_encode($n)); ?>)">
                                <div class="flex items-start gap-4 cursor-pointer">
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg flex-shrink-0 <?php echo $priorityColors[$n['priority']] ?? 'bg-slate-100 text-slate-500'; ?>">
                                        <i class="bi <?php echo $typeIcons[$n['type']] ?? 'bi-bell'; ?>"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100 <?php echo !$n['is_read'] ? 'text-blue-600 dark:text-blue-400' : ''; ?>">
                                            <?php echo htmlspecialchars($n['message']); ?>
                                        </p>
                                        <?php if($n['is_archived']): ?>
                                            <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">Archived</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 whitespace-nowrap" x-text="timeAgo('<?php echo $n['created_at']; ?>')"></p>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($notifications)): ?>
                        <tr>
                            <td colspan="3" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center text-slate-200">
                                        <i class="bi bi-bell-slash text-3xl"></i>
                                    </div>
                                    <p class="text-slate-400 text-sm italic">No notifications found.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center justify-between px-6 py-4">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing page <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $currentPage; ?></span> of <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $totalPages; ?></span>
        </p>
        <div class="flex space-x-1">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <button @click="applyFilters(<?php echo $i; ?>)" 
                   class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all border <?php echo $i === $currentPage ? 'bg-blue-600 text-white shadow-md border-blue-600' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-200 dark:border-slate-700'; ?>">
                    <?php echo $i; ?>
                </button>
            <?php endfor; ?>
        </div>
    </div>
</div>

<script>
function notificationManager() {
    return {
        selectedIds: [],
        filters: {
            status: '<?php echo $filters['status'] ?? ''; ?>',
            date_from: '<?php echo $filters['date_from'] ?? ''; ?>',
            date_to: '<?php echo $filters['date_to'] ?? ''; ?>'
        },
        get isAllSelected() {
            return this.selectedIds.length > 0 && this.selectedIds.length === <?php echo count($notifications); ?>;
        },
        toggleAll(e) {
            if (e.target.checked) {
                this.selectedIds = <?php echo json_encode(array_column($notifications, 'id')); ?>;
            } else {
                this.selectedIds = [];
            }
        },
        applyFilters(page = 1) {
            const params = new URLSearchParams();
            params.set('route', 'list_notifications');
            if (this.filters.status) params.set('status', this.filters.status);
            if (this.filters.date_from) params.set('date_from', this.filters.date_from);
            if (this.filters.date_to) params.set('date_to', this.filters.date_to);
            params.set('page', page);
            window.location.href = 'index.php?' + params.toString();
        },
        resetFilters() {
            this.filters = { status: '', date_from: '', date_to: '' };
            this.applyFilters(1);
        },
        async markSelectedAsRead() {
            try {
                const response = await fetch('index.php?route=notification_bulk_read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ ids: JSON.stringify(this.selectedIds) })
                });
                const result = await response.json();
                if (result.success) window.location.reload();
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Failed to update notifications.', 'error');
            }
        },
        async archiveSelected() {
            try {
                const response = await fetch('index.php?route=notification_bulk_archive', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ ids: JSON.stringify(this.selectedIds) })
                });
                const result = await response.json();
                if (result.success) window.location.reload();
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Failed to archive notifications.', 'error');
            }
        },
        goToNotification(n) {
            Alpine.store('app').goToNotification(n);
        },
        timeAgo(date) {
            return Alpine.store('app').timeAgo(date);
        }
    }
}
</script>
