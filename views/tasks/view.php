<?php
/** @var array $data */
include_once __DIR__ . '/../layouts/audit_helper.php';
$task = $data['task'];
$logs = $data['logs'];
$comments = (new \Modules\Tasks\TaskRepository())->getComments($task['id']);

$statusConfigs = [
    'todo' => ['label' => 'To Do', 'color' => 'bg-slate-100 text-slate-700', 'dot' => 'bg-slate-400'],
    'doing' => ['label' => 'Doing', 'color' => 'bg-blue-100 text-blue-700', 'dot' => 'bg-blue-500'],
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

/**
 * Simplified helper for Task Activity Log (replaces revision details)
 */
function renderTaskActivity(array $log): string {
    $user = htmlspecialchars($log['responsible_user'] ?? 'System');
    $action = $log['action'];
    
    if ($action === 'create') return "<strong>{$user}</strong> created the task";
    if ($action === 'delete') return "<strong>{$user}</strong> deleted the task";
    
    if ($action === 'update_status') {
        $new = $log['new_values'] ? json_decode($log['new_values'], true) : null;
        $status = $new['status'] ?? 'unknown';
        $statusLabel = ucwords(str_replace('_', ' ', $status));
        return "<strong>{$user}</strong> changed status to <span class=\"font-bold text-slate-700 dark:text-slate-300\">{$statusLabel}</span>";
    }

    if ($action === 'update') {
        return "<strong>{$user}</strong> updated task details";
    }
    
    return "<strong>{$user}</strong> performed an action";
}
?>

<div class="max-w-5xl mx-auto space-y-8" x-data="taskView(<?php echo htmlspecialchars(json_encode($comments)); ?>)">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 px-2 sm:px-0">
        <div class="flex items-center gap-5 min-w-0">
            <a href="index.php?route=list_tasks" 
               class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-400 hover:text-blue-600 hover:border-blue-200 dark:hover:border-blue-900 transition-all shadow-sm">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-tight truncate">
                    <?php echo htmlspecialchars($task['title'] ?? ''); ?>
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-[10px] font-black rounded-md uppercase tracking-wider">
                        Task #<?php echo $task['id']; ?>
                    </span>
                    <span class="text-sm text-slate-500 font-medium truncate">
                        Task Management &middot; <?php echo count($task['assignees']); ?> Assignees
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($task['status'] === 'todo' || $task['status'] === 'past_due'): ?>
                <button @click="updateStatus('doing')" :disabled="loading" class="w-full sm:w-auto px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-black shadow-xl shadow-blue-500/25 transition-all flex items-center justify-center gap-3 active:scale-95 disabled:opacity-50">
                    <i class="bi bi-play-fill text-xl" x-show="!loading"></i>
                    <i class="bi bi-arrow-repeat animate-spin text-xl" x-show="loading"></i>
                    <span class="whitespace-nowrap uppercase tracking-wider">Start Task</span>
                </button>
            <?php elseif ($task['status'] === 'doing'): ?>
                <button @click="updateStatus('done')" :disabled="loading" class="w-full sm:w-auto px-8 py-3.5 bg-green-600 hover:bg-green-700 text-white rounded-2xl text-sm font-black shadow-xl shadow-green-500/25 transition-all flex items-center justify-center gap-3 active:scale-95 disabled:opacity-50">
                    <i class="bi bi-check-lg text-xl" x-show="!loading"></i>
                    <i class="bi bi-arrow-repeat animate-spin text-xl" x-show="loading"></i>
                    <span class="whitespace-nowrap uppercase tracking-wider">Mark Done</span>
                </button>
            <?php endif; ?>
            
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="w-12 h-12 flex items-center justify-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-400 hover:text-slate-600 transition-all shadow-sm">
                    <i class="bi bi-three-dots text-xl"></i>
                </button>
                <div x-show="open" @click.away="open = false" x-cloak
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl shadow-2xl py-2 z-50 overflow-hidden">
                    <a href="index.php?route=edit_task&id=<?php echo $task['id']; ?>" class="flex items-center px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <i class="bi bi-pencil-square mr-3 text-slate-400"></i> Edit Task
                    </a>
                    <button @click="updateStatus('dropped')" :disabled="loading" class="w-full flex items-center px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors border-t dark:border-slate-700 disabled:opacity-50">
                        <i class="bi bi-slash-circle mr-3"></i> Drop Task
                    </button>
                    <button @click="confirmDelete()" :disabled="loading" class="w-full flex items-center px-4 py-2.5 text-sm font-medium text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors disabled:opacity-50">
                        <i class="bi bi-trash mr-3"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content (Left 2/3) -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Task Body -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors duration-300">
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?php echo $priorityColors[$task['priority']]; ?>">
                        <?php echo $task['priority']; ?>
                    </span>
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest <?php echo $statusConfigs[$task['status']]['color']; ?>">
                        <?php echo $statusConfigs[$task['status']]['label']; ?>
                    </span>
                </div>

                <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-4"><?php echo htmlspecialchars($task['title']); ?></h1>
                <p class="text-slate-600 dark:text-slate-400 leading-relaxed whitespace-pre-wrap mb-8"><?php echo htmlspecialchars($task['description']); ?></p>

                <?php if($task['tags']): ?>
                    <div class="flex flex-wrap gap-2 mb-8">
                        <?php foreach(explode(',', $task['tags']) as $tag): ?>
                            <span class="px-3 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-lg text-xs font-bold text-slate-500 dark:text-slate-400">#<?php echo trim($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Attachments Grid -->
                <div class="pt-8 border-t border-slate-50 dark:border-slate-800">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Attached Files</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                        <?php foreach($task['attachments'] as $file): ?>
                            <a href="<?php echo $file['file_path']; ?>" target="_blank" class="flex flex-col p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 hover:border-blue-400 transition-all group">
                                <div class="w-10 h-10 bg-white dark:bg-slate-900 rounded-xl flex items-center justify-center text-blue-600 mb-3 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="bi bi-file-earmark-arrow-down text-xl"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-700 dark:text-slate-200 line-clamp-1"><?php echo htmlspecialchars($file['file_name']); ?></p>
                                <p class="text-[9px] text-slate-400 font-bold uppercase mt-1"><?php echo strtoupper(pathinfo($file['file_name'], PATHINFO_EXTENSION)); ?></p>
                            </a>
                        <?php endforeach; ?>
                        <?php if(empty($task['attachments'])): ?>
                            <p class="col-span-full text-xs italic text-slate-400">No attachments for this task.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors duration-300">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Discussion</h3>
                
                <div class="space-y-6 mb-8 max-h-96 overflow-y-auto pr-4 custom-scrollbar">
                    <template x-for="comment in comments" :key="comment.id">
                        <div class="flex gap-4 animate-in slide-in-from-bottom-2 duration-300">
                            <div class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-600 shrink-0 border border-white dark:border-slate-900 overflow-hidden">
                                <template x-if="comment.profile_photo">
                                    <img :src="comment.profile_photo" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!comment.profile_photo">
                                    <span x-text="comment.username.charAt(0).toUpperCase()"></span>
                                </template>
                            </div>
                            <div class="flex-1 p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl rounded-tl-none border border-slate-100 dark:border-slate-800">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-xs font-bold text-slate-900 dark:text-slate-200" x-text="comment.username"></span>
                                    <span class="text-[10px] text-slate-400 font-medium" x-text="formatDate(comment.created_at)"></span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400" x-text="comment.comment"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="comments.length === 0" class="text-center py-8">
                        <p class="text-xs italic text-slate-400">No comments yet. Start the conversation!</p>
                    </div>
                </div>

                <form @submit.prevent="addComment" class="relative">
                    <textarea x-model="newComment" rows="3" placeholder="Add a comment..." 
                        class="w-full px-5 py-4 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm transition-all dark:text-slate-100"></textarea>
                    <button type="submit" :disabled="!newComment.trim() || loading" class="absolute bottom-3 right-3 bg-blue-600 hover:bg-blue-700 text-white p-2 rounded-xl shadow-lg transition-all disabled:opacity-50">
                        <i class="bi bi-send-fill px-1" x-show="!loading"></i>
                        <i class="bi bi-arrow-repeat animate-spin px-1" x-show="loading"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Sidebar Info (Right 1/3) -->
        <div class="space-y-8">
            <!-- Task Details Card -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
                <div class="space-y-6">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Assignees</p>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach($task['assignees'] as $u): ?>
                                <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 dark:bg-slate-800 rounded-xl border border-slate-100 dark:border-slate-700">
                                    <div class="w-5 h-5 rounded-lg bg-blue-600 text-white text-[9px] flex items-center justify-center font-bold overflow-hidden">
                                        <?php if(!empty($u['profile_photo'])): ?>
                                            <img src="<?php echo htmlspecialchars($u['profile_photo']); ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <span class="text-xs font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($u['username']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Deadline</p>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100"><?php echo $task['deadline'] ? date('d/m/Y', strtotime($task['deadline'])) : 'None'; ?></p>
                            <p class="text-[10px] text-slate-400 font-medium"><?php echo $task['deadline'] ? date('h:i A', strtotime($task['deadline'])) : ''; ?></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Recurrence</p>
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-100 capitalize"><?php echo $task['recurrence']; ?></p>
                            <?php if ($task['recurrence'] !== 'none' && !empty($task['recurrence_metadata'])): 
                                $meta = is_string($task['recurrence_metadata']) ? json_decode($task['recurrence_metadata'], true) : $task['recurrence_metadata'];
                            ?>
                                <p class="text-[10px] text-blue-500 font-bold mt-1">
                                    <?php 
                                        if ($task['recurrence'] === 'daily') echo 'Time: ' . ($meta['time'] ?? 'N/A');
                                        elseif ($task['recurrence'] === 'weekly') echo 'Every ' . ($meta['day'] ?? 'N/A');
                                        elseif ($task['recurrence'] === 'monthly') echo 'On Day ' . ($meta['date'] ?? 'N/A');
                                    ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Creator</p>
                        <div class="flex items-center gap-2 mt-2">
                            <div class="w-8 h-8 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xs font-bold text-slate-500 overflow-hidden">
                                <?php if(!empty($task['creator_photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($task['creator_photo']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($task['creator_name'] ?? 'U', 0, 1)); ?>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($task['creator_name'] ?? 'Unknown'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Activity Log -->
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Task Activity Log</h3>
                    <i class="bi bi-clock-history text-slate-400"></i>
                </div>
                <div class="flow-root overflow-y-auto max-h-[400px] pr-2 custom-scrollbar">
                    <ul role="list" class="-mb-8">
                        <?php foreach($logs as $index => $log): ?>
                            <li>
                                <div class="relative pb-8">
                                    <?php if($index !== count($logs)-1): ?>
                                        <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-100 dark:bg-slate-800" aria-hidden="true"></span>
                                    <?php endif; ?>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center ring-8 ring-white dark:ring-slate-900">
                                                <i class="bi <?php 
                                                    if ($log['action'] === 'create') echo 'bi-plus-circle-fill text-green-500';
                                                    elseif ($log['action'] === 'update_status') echo 'bi-arrow-repeat text-orange-500';
                                                    elseif ($log['action'] === 'delete') echo 'bi-trash-fill text-red-500';
                                                    else echo 'bi-pencil-fill text-blue-500';
                                                ?> text-[10px]"></i>
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div class="text-xs text-slate-600 dark:text-slate-400">
                                                <?php echo renderTaskActivity($log); ?>
                                            </div>
                                            <div class="whitespace-nowrap text-right text-[10px] font-bold text-slate-400 uppercase">
                                                <time datetime="<?php echo $log['created_at']; ?>">
                                                    <?php echo date('d/m/Y', strtotime($log['created_at'])); ?>
                                                </time>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php if(empty($logs)): ?>
                    <p class="text-xs italic text-slate-400 text-center">No activity recorded yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function taskView(initialComments) {
    return {
        newComment: '',
        comments: initialComments,
        loading: false,
        async updateStatus(status) {
            Alpine.store('app').confirm(
                'Update Status', 
                'Are you sure you want to change the task status to ' + status + '?',
                async () => {
                    this.loading = true;
                    try {
                        const response = await fetch('index.php?route=task_update_status', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                task_id: <?php echo $task['id']; ?>,
                                status: status,
                                csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                            })
                        });
                        const result = await response.json();
                        if (result.success) {
                            Alpine.store('app').addToast('Success', result.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            Alpine.store('app').addToast('Error', result.message, 'error');
                            this.loading = false;
                        }
                    } catch (e) {
                        Alpine.store('app').addToast('Error', 'Update failed.', 'error');
                        this.loading = false;
                    }
                }
            );
        },
        async addComment() {
            if (!this.newComment.trim()) return;
            this.loading = true;
            try {
                const response = await fetch('index.php?route=task_add_comment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        task_id: <?php echo $task['id']; ?>,
                        comment: this.newComment,
                        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                    })
                });
                const result = await response.json();
                if (result.success) {
                    this.comments.unshift({
                        id: Date.now(),
                        username: '<?php echo $_SESSION['username']; ?>',
                        profile_photo: '<?php echo $_SESSION['profile_photo'] ?? ''; ?>',
                        comment: this.newComment,
                        created_at: new Date().toISOString()
                    });
                    this.newComment = '';
                    Alpine.store('app').addToast('Success', result.message, 'success');
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Failed to post comment.', 'error');
            } finally {
                this.loading = false;
            }
        },
        async confirmDelete() {
            Alpine.store('app').confirm(
                'Delete Task',
                'Are you sure you want to permanently delete this task? This action cannot be undone.',
                async () => {
                    this.loading = true;
                    try {
                        const response = await fetch('index.php?route=task_delete&id=<?php echo $task['id']; ?>');
                        const result = await response.json();
                        if (result.success) {
                            Alpine.store('app').addToast('Success', 'Task deleted successfully.', 'success');
                            setTimeout(() => window.location.href = 'index.php?route=list_tasks', 1000);
                        } else {
                            Alpine.store('app').addToast('Error', result.message, 'error');
                            this.loading = false;
                        }
                    } catch (e) {
                        Alpine.store('app').addToast('Error', 'Failed to delete task.', 'error');
                        this.loading = false;
                    }
                }
            );
        },
        formatDate(dateString) {
            const date = new Date(dateString);
            const d = date.getDate().toString().padStart(2, '0');
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            const y = date.getFullYear();
            const h = date.getHours().toString().padStart(2, '0');
            const min = date.getMinutes().toString().padStart(2, '0');
            return `${d}/${m}/${y}, ${h}:${min}`;
        }
    }
}
</script>
