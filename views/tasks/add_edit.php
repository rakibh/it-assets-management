<?php
/** @var array $data */
$task = $data['task'] ?? null;
$isEdit = $data['isEdit'] ?? false;
$assigneeIds = $data['assigneeIds'] ?? [];
$users = $data['users'] ?? [];

$priorities = ['low', 'medium', 'high', 'urgent'];
$recurrences = ['none', 'daily', 'weekly', 'monthly'];
$days = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

// Prepare JS data safely with extreme defensiveness against null task
$jsData = [
    'task_id' => '',
    'title' => '',
    'description' => '',
    'tags' => '',
    'priority' => 'medium',
    'status' => 'todo',
    'deadline' => '',
    'recurrence' => 'none',
    'recurrence_metadata' => ['time' => '09:00', 'day' => 'MON', 'date' => '1'],
    'assignees' => array_map('strval', $assigneeIds),
    'csrf_token' => $_SESSION['csrf_token']
];

if (is_array($task)) {
    $jsData['task_id'] = $task['id'] ?? '';
    $jsData['title'] = $task['title'] ?? '';
    $jsData['description'] = $task['description'] ?? '';
    $jsData['tags'] = $task['tags'] ?? '';
    $jsData['priority'] = $task['priority'] ?? 'medium';
    $jsData['status'] = $task['status'] ?? 'todo';
    
    if (!empty($task['deadline'])) {
        $jsData['deadline'] = date('Y-m-d\TH:i', strtotime($task['deadline']));
    }
    
    if (!empty($task['recurrence'])) {
        $jsData['recurrence'] = $task['recurrence'];
    }
    
    if (!empty($task['recurrence_metadata'])) {
        $meta = $task['recurrence_metadata'];
        $jsData['recurrence_metadata'] = is_string($meta) ? json_decode($meta, true) : $meta;
    }
}

$allUserIds = array_map('strval', array_column($users, 'id'));
?>

<div class="max-w-4xl mx-auto" x-data="taskForm()">
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden transition-all duration-300">
        <!-- Header -->
        <div class="bg-slate-800 dark:bg-slate-950 px-8 py-6 flex justify-between items-center text-white">
            <div>
                <h2 class="text-xl font-bold"><?php echo $isEdit ? 'Edit Task' : 'Create New Task'; ?></h2>
                <p class="text-xs text-slate-400 mt-1">Assign and manage workplace responsibilities.</p>
            </div>
            <a href="index.php?route=list_tasks" class="p-2 bg-white/10 hover:bg-white/20 rounded-xl transition-colors">
                <i class="bi bi-x-lg"></i>
            </a>
        </div>

        <form @submit.prevent="submitForm" class="p-8 space-y-8">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="space-y-6">
                <div>
                    <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Task Title *</label>
                    <input type="text" x-model="formData.title" required 
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Description</label>
                    <textarea x-model="formData.description" rows="3" 
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:text-slate-100"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Tags (Optional)</label>
                    <input type="text" x-model="formData.tags" placeholder="e.g. urgent, it-support"
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:text-slate-100">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-8 border-t border-slate-50 dark:border-slate-800">
                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Priority Level *</label>
                        <select x-model="formData.priority" required class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100">
                            <?php foreach($priorities as $p): ?>
                                <option value="<?php echo $p; ?>"><?php echo ucfirst($p); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Deadline *</label>
                        <input type="datetime-local" x-model="formData.deadline" required class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Assign To Employees *</label>
                        <div class="relative" x-data="{ open: false }">
                            <button type="button" @click="open = !open" 
                                class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-left text-sm flex items-center justify-between shadow-sm">
                                <span x-text="formData.assignees.length ? formData.assignees.length + ' selected' : 'Select Employees'" 
                                      :class="formData.assignees.length ? 'text-slate-900 dark:text-white font-bold' : 'text-slate-400'">Select Employees</span>
                                <i class="bi bi-chevron-down text-slate-400"></i>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" x-cloak class="absolute z-50 mt-2 w-full max-h-64 overflow-y-auto bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl shadow-2xl p-2 custom-scrollbar">
                                <label class="flex items-center px-4 py-3 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer border-b border-slate-50 dark:border-slate-800 mb-1">
                                    <input type="checkbox" :checked="isAllSelected()" @change="toggleAll($event.target.checked)" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-3 text-sm font-black text-blue-600 uppercase tracking-widest">Select All</span>
                                </label>
                                <?php foreach($users as $u): ?>
                                    <label class="flex items-center px-4 py-2.5 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700/50 cursor-pointer">
                                        <input type="checkbox" value="<?php echo $u['id']; ?>" x-model="formData.assignees" class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                        <div class="ml-3 flex flex-col">
                                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($u['username']); ?></span>
                                            <span class="text-[10px] text-slate-400 font-bold uppercase"><?php echo $u['employee_id']; ?></span>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Recurrence</label>
                        <select x-model="formData.recurrence" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                            <?php foreach($recurrences as $r): ?>
                                <option value="<?php echo $r; ?>"><?php echo ucfirst($r); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Recurrence Config -->
            <div x-show="formData.recurrence !== 'none'" x-cloak x-transition class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 space-y-4">
                <div x-show="formData.recurrence === 'daily'">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 tracking-widest">Execution Time</label>
                    <input type="time" x-model="formData.recurrence_metadata.time" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm">
                </div>
                <div x-show="formData.recurrence === 'weekly'">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-3 tracking-widest">Preferred Day</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($days as $day): ?>
                            <label class="cursor-pointer">
                                <input type="radio" x-model="formData.recurrence_metadata.day" value="<?php echo $day; ?>" class="sr-only">
                                <span class="px-3 py-1.5 rounded-lg border text-[10px] font-black transition-all"
                                    :class="formData.recurrence_metadata.day === '<?php echo $day; ?>' ? 'bg-blue-600 text-white border-blue-600 shadow-md' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-500'"><?php echo $day; ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div x-show="formData.recurrence === 'monthly'">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 tracking-widest">Day of Month</label>
                    <select x-model="formData.recurrence_metadata.date" class="px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 dark:bg-slate-800 text-sm">
                        <?php for($i=1; $i<=31; $i++): ?> <option value="<?php echo $i; ?>"><?php echo $i; ?></option> <?php endfor; ?>
                    </select>
                </div>
            </div>

            <!-- Attachments -->
            <div class="pt-8 border-t border-slate-50 dark:border-slate-800">
                <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-4">Task Attachments</label>
                <div class="relative group p-8 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-3xl text-center hover:border-blue-400 transition-colors">
                    <input type="file" multiple name="attachments[]" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" @change="handleFiles($event)">
                    <div class="space-y-2">
                        <i class="bi bi-cloud-arrow-up text-3xl text-blue-600 mb-2 block"></i>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200">Click to upload files</p>
                    </div>
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    <template x-for="(file, index) in selectedFiles" :key="index">
                        <div class="flex items-center gap-2 px-3 py-1 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs">
                            <span x-text="file.name" class="max-w-[200px] truncate"></span>
                            <button type="button" @click="selectedFiles.splice(index, 1)" class="text-red-500 font-bold">×</button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-4 pt-10">
                <a href="index.php?route=list_tasks" class="px-8 py-3 text-sm font-bold text-slate-500 hover:text-slate-800 transition-all">Cancel</a>
                <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-10 py-3.5 rounded-2xl text-sm font-bold shadow-xl shadow-blue-500/30 transition-all disabled:opacity-50 min-w-[200px] flex items-center justify-center">
                    <span x-show="!loading"><?php echo $isEdit ? 'Save Changes' : 'Create Task'; ?></span>
                    <span x-show="loading" class="flex items-center"><i class="bi bi-arrow-repeat animate-spin mr-2"></i> Saving...</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function taskForm() {
    return {
        loading: false,
        selectedFiles: [],
        formData: <?php echo json_encode($jsData); ?>,
        allUserIds: <?php echo json_encode($allUserIds); ?>,
        isAllSelected() {
            return this.allUserIds.length > 0 && this.formData.assignees.length === this.allUserIds.length;
        },
        toggleAll(checked) {
            this.formData.assignees = checked ? [...this.allUserIds] : [];
        },
        handleFiles(e) {
            this.selectedFiles = [...this.selectedFiles, ...Array.from(e.target.files)];
        },
        async submitForm() {
            if (!this.formData.assignees.length) {
                Alpine.store('app').addToast('Selection Error', 'Please select at least one assignee.', 'error');
                return;
            }
            this.loading = true;
            try {
                const body = new FormData();
                Object.keys(this.formData).forEach(key => {
                    if (Array.isArray(this.formData[key])) {
                        this.formData[key].forEach(val => body.append(key + '[]', val));
                    } else if (key === 'recurrence_metadata') {
                        body.append(key, JSON.stringify(this.formData[key]));
                    } else {
                        body.append(key, this.formData[key]);
                    }
                });
                
                this.selectedFiles.forEach(file => body.append('attachments[]', file));

                const url = this.formData.task_id ? 'index.php?route=task_update' : 'index.php?route=task_store';
                const response = await fetch(url, {
                    method: 'POST',
                    body: body
                });
                
                const result = await response.json();
                if (result.success) {
                    Alpine.store('app').addToast('Success', result.message, 'success');
                    setTimeout(() => window.location.href = result.redirect, 1000);
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                    this.loading = false;
                }
            } catch (e) {
                Alpine.store('app').addToast('Critical Error', 'Submission failed.', 'error');
                this.loading = false;
            }
        }
    }
}
</script>
