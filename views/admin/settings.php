<?php
/** @var array $data */
$settings = $data['settings'];

// Default values if not set
$defaults = [
    'system_name' => 'IT Management System',
    'records_per_page' => '20',
    'notification_refresh' => '30',
    'user_allowed_extensions' => 'jpg,png,jpeg',
    'user_max_upload_size' => '2',
    'task_allowed_extensions' => 'pdf,jpg,png,jpeg,doc,docx,txt,xls,xlsx,csv',
    'task_max_upload_size' => '5',
    'equipment_allowed_extensions' => 'jpg,png,jpeg',
    'equipment_max_upload_size' => '5',
    'warranty_allowed_extensions' => 'pdf,jpg,png,jpeg',
    'warranty_max_upload_size' => '10',
    'low_priority_color' => '#94a3b8',
    'medium_priority_color' => '#3b82f6',
    'high_priority_color' => '#f97316',
    'urgent_priority_color' => '#ef4444'
];

$current = array_merge($defaults, $settings);
?>

<div class="max-w-4xl mx-auto space-y-8" x-data="settingsPage(<?php echo htmlspecialchars(json_encode($current)); ?>)">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">System Settings</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Configure global application behavior and module-specific policies.</p>
        </div>
        <button @click="saveSettings" :disabled="loading" 
            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-blue-500/20 transition-all flex items-center disabled:opacity-50">
            <i class="bi bi-save2 mr-2" x-show="!loading"></i>
            <i class="bi bi-arrow-repeat animate-spin mr-2" x-show="loading"></i>
            Save Changes
        </button>
    </div>

    <div class="grid grid-cols-1 gap-8">
        <!-- General Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-display mr-2"></i> General Configuration
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">System Name</label>
                    <input type="text" x-model="config.system_name" 
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Records Per Page</label>
                    <select x-model="config.records_per_page" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100">
                        <option value="10">10 Records</option>
                        <option value="20">20 Records</option>
                        <option value="50">50 Records</option>
                        <option value="100">100 Records</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Session Timeout (Minutes)</label>
                    <input type="number" x-model="config.session_timeout" min="30"
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                    <p class="text-[10px] text-slate-400 mt-2 italic">Minimum 30 minutes.</p>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Notification Refresh (Seconds)</label>
                    <input type="number" x-model="config.notification_refresh" min="5"
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                </div>
            </div>
        </div>

        <!-- User Module Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-person-gear mr-2"></i> User Module (Profiles)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Allowed Extensions</label>
                    <input type="text" x-model="config.user_allowed_extensions" 
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono dark:text-slate-100 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Max Photo Size (MB)</label>
                    <input type="number" x-model="config.user_max_upload_size" 
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                </div>
            </div>
        </div>

        <!-- Task Module Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-list-task mr-2"></i> Task Module (Attachments)
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Allowed Extensions</label>
                    <input type="text" x-model="config.task_allowed_extensions" 
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono dark:text-slate-100 transition-all">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Max Attachment Size (MB)</label>
                    <input type="number" x-model="config.task_max_upload_size" 
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                </div>
            </div>
        </div>

        <!-- Equipment Module Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-pc-display mr-2"></i> Equipment Module
            </h3>
            <div class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Hardware Photos (Gallery)</p>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Allowed Extensions</label>
                        <input type="text" x-model="config.equipment_allowed_extensions" 
                            class="w-full px-5 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono dark:text-slate-100 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Max Photo Size (MB)</label>
                        <input type="number" x-model="config.equipment_max_upload_size" 
                            class="w-full px-5 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest">Warranty Documents</p>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Allowed Extensions</label>
                        <input type="text" x-model="config.warranty_allowed_extensions" 
                            class="w-full px-5 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-mono dark:text-slate-100 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Max File Size (MB)</label>
                        <input type="number" x-model="config.warranty_max_upload_size" 
                            class="w-full px-5 py-3.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                    </div>
                </div>
            </div>
        </div>

        <!-- Theme & UI Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-palette mr-2"></i> Priority Colors
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Low</label>
                    <div class="flex gap-2">
                        <input type="color" x-model="config.low_priority_color" class="h-10 w-10 border-none bg-transparent cursor-pointer">
                        <input type="text" x-model="config.low_priority_color" class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-mono dark:text-white uppercase">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Medium</label>
                    <div class="flex gap-2">
                        <input type="color" x-model="config.medium_priority_color" class="h-10 w-10 border-none bg-transparent cursor-pointer">
                        <input type="text" x-model="config.medium_priority_color" class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-mono dark:text-white uppercase">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">High</label>
                    <div class="flex gap-2">
                        <input type="color" x-model="config.high_priority_color" class="h-10 w-10 border-none bg-transparent cursor-pointer">
                        <input type="text" x-model="config.high_priority_color" class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-mono dark:text-white uppercase">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest">Urgent</label>
                    <div class="flex gap-2">
                        <input type="color" x-model="config.urgent_priority_color" class="h-10 w-10 border-none bg-transparent cursor-pointer">
                        <input type="text" x-model="config.urgent_priority_color" class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-mono dark:text-white uppercase">
                    </div>
                </div>
            </div>
        </div>

        <!-- Log Retention Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-clock-history mr-2"></i> Log Retention & Cleanup
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Retention Period (Days)</label>
                    <input type="number" x-model="config.log_retention_days" min="30" max="365"
                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                    <p class="text-[10px] text-slate-400 mt-2 italic">Minimum 30 days, Default 90 days.</p>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Auto-Cleanup</label>
                    <div class="flex items-center h-[52px]">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="config.enable_log_cleanup" :checked="config.enable_log_cleanup == '1'" @change="config.enable_log_cleanup = $event.target.checked ? '1' : '0'" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                            <span class="ml-3 text-sm font-bold text-slate-600 dark:text-slate-400" x-text="config.enable_log_cleanup == '1' ? 'Enabled' : 'Disabled'"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function settingsPage(initialData) {
    return {
        config: initialData,
        loading: false,
        async saveSettings() {
            this.loading = true;
            try {
                const response = await fetch('index.php?route=settings_save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ...this.config,
                        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                    })
                });
                const result = await response.json();
                if (result.success) {
                    Alpine.store('app').addToast('Success', result.message, 'success');
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Failed to save settings.', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
