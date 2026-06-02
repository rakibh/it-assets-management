<?php
/** @var array $data */
$tasks = $data['tasks'];
$counts = $data['counts'];
$filters = $data['filters'];
$users = $data['users'];

$activeTab = $filters['tab'] ?? 'board';

$statusConfigs = [
    'todo' => ['label' => 'To Do', 'color' => 'bg-slate-100 text-slate-700', 'dot' => 'bg-slate-400'],
    'doing' => ['label' => 'Doing', 'color' => 'bg-blue-100 text-blue-700', 'dot' => 'bg-blue-50'],
    'past_due' => ['label' => 'Past Due', 'color' => 'bg-red-100 text-red-700', 'dot' => 'bg-red-500'],
    'done' => ['label' => 'Done', 'color' => 'bg-green-100 text-green-700', 'dot' => 'bg-green-500'],
    'dropped' => ['label' => 'Dropped', 'color' => 'bg-gray-100 text-gray-500', 'dot' => 'bg-gray-400'],
];

$priorityColors = [
    'low' => 'bg-slate-100 text-slate-600',
    'medium' => 'bg-blue-100 text-blue-600',
    'high' => 'bg-orange-100 text-orange-600',
    'urgent' => 'bg-red-100 text-red-600',
];
?>

<div x-data="taskManagement()">
    <!-- Stats & Tabs Header -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Part 1: Board Counters (To Do, Doing, Past Due) -->
        <div class="flex gap-4">
            <a href="index.php?route=list_tasks&tab=board" 
               class="flex-1 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-md group <?php echo $activeTab === 'board' ? 'ring-2 ring-blue-500' : ''; ?>">
                <div class="flex items-center justify-between mb-2">
                    <span class="p-2 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-400 group-hover:text-blue-500 transition-colors">
                        <i class="bi bi-layout-three-columns"></i>
                    </span>
                    <span class="text-2xl font-bold text-slate-900 dark:text-white"><?php echo $counts['todo'] + $counts['doing'] + $counts['past_due']; ?></span>
                </div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Active Board</p>
            </a>
            
            <div class="flex flex-col gap-2 flex-1">
                <div class="flex justify-between items-center p-2 px-4 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800">
                    <span class="text-[10px] font-bold text-slate-500 uppercase">To Do</span>
                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300"><?php echo $counts['todo']; ?></span>
                </div>
                <div class="flex justify-between items-center p-2 px-4 bg-blue-50 dark:bg-blue-900/10 rounded-xl border border-blue-100 dark:border-blue-900/30">
                    <span class="text-[10px] font-bold text-blue-600 uppercase">Doing</span>
                    <span class="text-sm font-bold text-blue-700 dark:text-blue-400"><?php echo $counts['doing']; ?></span>
                </div>
                <div class="flex justify-between items-center p-2 px-4 bg-red-50 dark:bg-red-900/10 rounded-xl border border-red-100 dark:border-red-900/30">
                    <span class="text-[10px] font-bold text-red-600 uppercase">Past Due</span>
                    <span class="text-sm font-bold text-red-700 dark:text-red-400"><?php echo $counts['past_due']; ?></span>
                </div>
            </div>
        </div>

        <!-- Part 2: Tab Counters (All, Done, Dropped) -->
        <div class="flex items-end gap-3">
            <a href="index.php?route=list_tasks&tab=all" 
               class="flex-1 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-md <?php echo $activeTab === 'all' ? 'ring-2 ring-indigo-500' : ''; ?>">
                <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">All Tasks</p>
                <p class="text-xl font-bold text-slate-900 dark:text-white"><?php echo $counts['all']; ?></p>
            </a>
            <a href="index.php?route=list_tasks&tab=done" 
               class="flex-1 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-md <?php echo $activeTab === 'done' ? 'ring-2 ring-green-500' : ''; ?>">
                <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Completed</p>
                <p class="text-xl font-bold text-green-600"><?php echo $counts['done']; ?></p>
            </a>
            <a href="index.php?route=list_tasks&tab=dropped" 
               class="flex-1 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm transition-all hover:shadow-md <?php echo $activeTab === 'dropped' ? 'ring-2 ring-slate-400' : ''; ?>">
                <p class="text-[10px] font-bold text-slate-500 uppercase mb-1">Dropped</p>
                <p class="text-xl font-bold text-slate-500"><?php echo $counts['dropped']; ?></p>
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm mb-8 flex flex-wrap items-center justify-between gap-4 transition-colors duration-300">
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <i class="bi bi-search absolute left-3 top-2.5 text-slate-400"></i>
                <input type="text" x-model="filters.search" @input.debounce.500ms="applyFilters(1)" 
                    placeholder="Search title, tags, names..." 
                    class="pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none w-64 dark:text-slate-100">
            </div>
            
            <select x-model="filters.priority" @change="applyFilters(1)" 
                class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none dark:text-slate-200">
                <option value="">All Priorities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>

            <select x-model="filters.assignee_id" @change="applyFilters(1)" 
                class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none dark:text-slate-200">
                <option value="">All Assignees</option>
                <?php foreach($users as $u): ?>
                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?></option>
                <?php endforeach; ?>
            </select>

            <select x-model="sort_option" @change="applySorting(1)" 
                class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none dark:text-slate-200 font-bold">
                <option value="deadline_ASC">Deadline (Soonest)</option>
                <option value="deadline_DESC">Deadline (Latest)</option>
                <option value="created_at_DESC">Newest First</option>
                <option value="created_at_ASC">Oldest First</option>
            </select>
        </div>

        <div class="flex items-center gap-3">
            <button @click="exportTasks" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 px-3 py-2 rounded-xl text-sm font-bold flex items-center transition-all">
                <i class="bi bi-download mr-2"></i> Export
            </button>
            <button @click="openAddModal" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-bold flex items-center shadow-lg shadow-blue-500/20 transition-all">
                <i class="bi bi-plus-lg mr-2"></i> Create Task
            </button>
        </div>
    </div>

    <!-- MAIN VIEW AREA -->
    <?php if ($activeTab === 'board'): ?>
        <!-- BOARD VIEW -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach(['todo', 'doing', 'past_due'] as $status): 
                $cfg = $statusConfigs[$status];
                $statusTasks = array_filter($tasks, fn($t) => $t['status'] === $status);
            ?>
                <div class="flex flex-col min-w-[300px]">
                    <div class="flex items-center justify-between mb-6 px-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full <?php echo $cfg['dot']; ?> shadow-[0_0_8px_rgba(0,0,0,0.1)]"></span>
                            <h3 class="font-bold text-slate-700 dark:text-slate-200 text-sm uppercase tracking-widest"><?php echo $cfg['label']; ?></h3>
                        </div>
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-lg">
                            <?php echo count($statusTasks); ?>
                        </span>
                    </div>

                    <div class="space-y-4 min-h-[600px] p-2 rounded-3xl bg-slate-100/50 dark:bg-slate-900/30 border-2 border-dashed border-slate-200 dark:border-slate-800 transition-colors">
                        <?php foreach ($statusTasks as $task): ?>
                            <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-800 hover:shadow-xl hover:-translate-y-1 transition-all cursor-pointer group"
                                 @click="viewTask(<?php echo $task['id']; ?>)">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="px-2.5 py-1 rounded-lg text-[9px] font-black uppercase tracking-tighter <?php echo $priorityColors[$task['priority']]; ?>">
                                        <?php echo $task['priority']; ?>
                                    </span>
                                    <div class="flex items-center gap-2">
                                        <?php if($task['recurrence'] !== 'none'): ?>
                                            <i class="bi bi-repeat text-blue-500 text-xs"></i>
                                        <?php endif; ?>
                                        <?php if($task['attachment_count'] > 0): ?>
                                            <i class="bi bi-paperclip text-slate-400 text-xs"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <h4 class="font-bold text-slate-800 dark:text-slate-100 text-sm mb-2 leading-snug group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($task['title']); ?></h4>
                                <p class="text-slate-500 dark:text-slate-400 text-xs line-clamp-2 mb-4"><?php echo htmlspecialchars($task['description']); ?></p>
                                
                                <?php if($task['tags']): ?>
                                    <div class="flex flex-wrap gap-1 mb-4">
                                        <?php foreach(explode(',', $task['tags']) as $tag): ?>
                                            <span class="text-[9px] font-bold text-blue-500 bg-blue-50 dark:bg-blue-900/20 px-1.5 py-0.5 rounded"><?php echo trim($tag); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="flex items-center justify-between pt-4 border-t border-slate-50 dark:border-slate-800">
                                    <div class="flex -space-x-2">
                                        <?php 
                                        $names = explode(', ', $task['assignee_names'] ?? '');
                                        $photos = explode('|||', $task['assignee_photos'] ?? '');
                                        foreach(array_slice($names, 0, 3) as $i => $name): if(empty($name)) continue; 
                                            $photo = $photos[$i] ?? null;
                                        ?>
                                            <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 border-2 border-white dark:border-slate-900 flex items-center justify-center text-[10px] font-bold text-slate-600 dark:text-slate-400 overflow-hidden" title="<?php echo htmlspecialchars($name); ?>">
                                                <?php if($photo): ?>
                                                    <img src="<?php echo htmlspecialchars($photo); ?>" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($name, 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if(count($names) > 3): ?>
                                            <div class="w-7 h-7 rounded-full bg-slate-50 dark:bg-slate-800 border-2 border-white dark:border-slate-900 flex items-center justify-center text-[9px] font-bold text-slate-400">
                                                +<?php echo count($names)-3; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="flex flex-col items-end">
                                        <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter mb-0.5">Deadline</span>
                                        <span class="text-[10px] font-bold <?php echo $status === 'past_due' ? 'text-red-500' : 'text-slate-600 dark:text-slate-300'; ?>">
                                            <?php echo $task['deadline'] ? date('d/m/Y', strtotime($task['deadline'])) : 'No Date'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- TAB VIEW (List Style for All, Done, Dropped) -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-300">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">
                    <tr>
                        <th class="px-6 py-4">Task Info</th>
                        <th class="px-6 py-4">Assignees</th>
                        <th class="px-6 py-4">Priority</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Deadline</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <?php foreach($tasks as $task): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors cursor-pointer group" @click="viewTask(<?php echo $task['id']; ?>)">
                            <td class="px-6 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-2 h-2 rounded-full <?php echo $statusConfigs[$task['status']]['dot']; ?>"></div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($task['title']); ?></p>
                                        <p class="text-xs text-slate-400 mt-0.5"><?php echo $task['attachment_count']; ?> attachments • <?php echo $task['comment_count']; ?> comments</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex -space-x-1.5 overflow-hidden">
                                    <?php 
                                    $names = explode(', ', $task['assignee_names'] ?? '');
                                    $photos = explode('|||', $task['assignee_photos'] ?? '');
                                    foreach(array_slice($names, 0, 4) as $i => $name): if(empty($name)) continue; 
                                        $photo = $photos[$i] ?? null;
                                    ?>
                                        <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-800 ring-2 ring-white dark:ring-slate-900 flex items-center justify-center text-[9px] font-bold text-slate-600 dark:text-slate-400 overflow-hidden" title="<?php echo htmlspecialchars($name); ?>">
                                            <?php if($photo): ?>
                                                <img src="<?php echo htmlspecialchars($photo); ?>" class="w-full h-full object-cover">
                                            <?php else: ?>
                                                <?php echo strtoupper(substr($name, 0, 1)); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase <?php echo $priorityColors[$task['priority']]; ?>">
                                    <?php echo $task['priority']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-xs font-semibold text-slate-600 dark:text-slate-400 capitalize"><?php echo $task['status']; ?></span>
                            </td>
                            <td class="px-6 py-5">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                    <?php echo $task['deadline'] ? date('d/m/Y', strtotime($task['deadline'])) : '-'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <button class="p-2 text-slate-400 hover:text-blue-600 transition-colors">
                                    <i class="bi bi-arrow-right"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($tasks)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-20 text-center">
                                <div class="max-w-xs mx-auto text-slate-400">
                                    <i class="bi bi-inbox text-5xl mb-4 block opacity-20"></i>
                                    <p class="text-sm font-medium italic">No tasks found matching your criteria.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Unified Pagination -->
    <?php if ($activeTab === 'all'): ?>
    <div class="mt-8 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex items-center justify-between px-6 py-4 transition-colors">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing page <span class="font-bold text-slate-800 dark:text-slate-100" x-text="currentPage"></span> of <span class="font-bold text-slate-800 dark:text-slate-100" x-text="totalPages"></span> (<?php echo $data['total']; ?> tasks)
        </p>
        <div class="flex space-x-1">
            <template x-for="p in Array.from({length: totalPages}, (v, i) => i + 1)" :key="p">
                <button @click="goToPage(p)" 
                        class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all border"
                        :class="p === currentPage ? 'bg-blue-600 text-white shadow-md border-blue-600' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-200 dark:border-slate-700'">
                    <span x-text="p"></span>
                </button>
            </template>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function taskManagement() {
    const params = new URLSearchParams(window.location.search);
    const sortBy = params.get('sort_by') || 'deadline';
    const sortDir = params.get('sort_dir') || 'ASC';

    return {
        filters: {
            search: '<?php echo $filters['search'] ?? ''; ?>',
            priority: '<?php echo $filters['priority'] ?? ''; ?>',
            assignee_id: '<?php echo $filters['assignee_id'] ?? ''; ?>',
            tab: '<?php echo $activeTab; ?>'
        },
        sort_option: `${sortBy}_${sortDir}`,
        totalPages: <?php echo $data['pages']; ?>,
        currentPage: <?php echo $data['currentPage']; ?>,
        applyFilters(page = 1) {
            const params = new URLSearchParams(window.location.search);
            params.set('route', 'list_tasks');
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) params.set(key, this.filters[key]);
                else params.delete(key);
            });
            params.set('page', page.toString());
            window.location.href = 'index.php?' + params.toString();
        },
        applySorting(page = 1) {
            const parts = this.sort_option.split('_');
            const dir = parts.pop();
            const field = parts.join('_');
            const params = new URLSearchParams(window.location.search);
            params.set('sort_by', field);
            params.set('sort_dir', dir);
            params.set('page', page.toString());
            window.location.href = 'index.php?' + params.toString();
        },
        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            const params = new URLSearchParams(window.location.search);
            params.set('page', page);
            window.location.href = 'index.php?' + params.toString();
        },
        openAddModal() {
            window.location.href = 'index.php?route=add_task';
        },
        viewTask(id) {
            window.location.href = 'index.php?route=view_task&id=' + id;
        },
        exportTasks() {
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(key => {
                if (this.filters[key]) params.set(key, this.filters[key]);
            });
            
            const sortBy = new URLSearchParams(window.location.search).get('sort_by') || 'deadline';
            const sortDir = new URLSearchParams(window.location.search).get('sort_dir') || 'ASC';
            params.set('sort_by', sortBy);
            params.set('sort_dir', sortDir);

            Alpine.store('app').addToast('Export', 'Exporting current view to CSV...', 'success');
            window.location.href = 'index.php?route=task_export&' + params.toString();
        }
    }
}
</script>
