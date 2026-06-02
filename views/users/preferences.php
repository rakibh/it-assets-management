<?php
/** @var array $data */
$prefs = $data['preferences'];
$timezones = $data['timezones'];
?>

<div class="max-w-4xl mx-auto" x-data="preferencesPage(<?php echo htmlspecialchars(json_encode($prefs)); ?>)">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">Preferences</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Personalize your system experience and regional settings.</p>
        </div>
        <button @click="savePreferences" :disabled="loading" 
            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-2xl text-sm font-bold shadow-xl shadow-blue-500/20 transition-all flex items-center disabled:opacity-50">
            <i class="bi bi-save2 mr-2" x-show="!loading"></i>
            <i class="bi bi-arrow-repeat animate-spin mr-2" x-show="loading"></i>
            Save Preferences
        </button>
    </div>

    <!-- Cards Container -->
    <div class="flex flex-col gap-8">
        <!-- Theme & Appearance -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-palette mr-2"></i> Appearance
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-4">Color Theme</label>
                    <div class="flex gap-4">
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="theme" value="light" x-model="config.theme" class="sr-only">
                            <div class="p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-3"
                                :class="config.theme === 'light' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/10' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200'">
                                <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center text-slate-400 group-hover:text-blue-500 transition-colors">
                                    <i class="bi bi-sun-fill text-xl"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Light Mode</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="theme" value="dark" x-model="config.theme" class="sr-only">
                            <div class="p-4 rounded-2xl border-2 transition-all flex flex-col items-center gap-3"
                                :class="config.theme === 'dark' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/10' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200'">
                                <div class="w-12 h-12 rounded-full bg-slate-800 shadow-sm flex items-center justify-center text-slate-400 group-hover:text-blue-500 transition-colors">
                                    <i class="bi bi-moon-stars-fill text-xl"></i>
                                </div>
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Dark Mode</span>
                            </div>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-4">Toast Notification Position</label>
                    <div class="grid grid-cols-1 gap-3">
                        <label class="flex items-center gap-3 p-4 border border-slate-100 dark:border-slate-800 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                            :class="config.toast_position === 'top-right' ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : ''">
                            <input type="radio" name="toast_position" value="top-right" x-model="config.toast_position" class="sr-only">
                            <div class="w-2 h-2 rounded-full" :class="config.toast_position === 'top-right' ? 'bg-blue-500' : 'bg-slate-300'"></div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Top Right</span>
                        </label>
                        <label class="flex items-center gap-3 p-4 border border-slate-100 dark:border-slate-800 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                            :class="config.toast_position === 'bottom-right' ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : ''">
                            <input type="radio" name="toast_position" value="bottom-right" x-model="config.toast_position" class="sr-only">
                            <div class="w-2 h-2 rounded-full" :class="config.toast_position === 'bottom-right' ? 'bg-blue-500' : 'bg-slate-300'"></div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Bottom Right</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Regional Settings -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-globe mr-2"></i> Regional Settings
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Time Zone</label>
                    <select x-model="config.timezone" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        <?php foreach ($timezones as $tz): ?>
                            <option value="<?php echo $tz; ?>"><?php echo $tz; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-2">Time Format</label>
                    <div class="flex gap-4">
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="time_format" value="12" x-model="config.time_format" class="sr-only">
                            <div class="p-4 rounded-2xl border-2 transition-all text-center"
                                :class="config.time_format === '12' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/10' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200'">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">12-Hour (01:30 PM)</span>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer group">
                            <input type="radio" name="time_format" value="24" x-model="config.time_format" class="sr-only">
                            <div class="p-4 rounded-2xl border-2 transition-all text-center"
                                :class="config.time_format === '24' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/10' : 'border-slate-100 dark:border-slate-800 hover:border-slate-200'">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-300">24-Hour (13:30)</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notification Preferences -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-bell mr-2"></i> Notifications
            </h3>
            
            <div class="flex flex-col gap-6">
                <!-- Master Toggle -->
                <div class="flex items-center justify-between p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-4">
                        <div class="p-3 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600">
                            <i class="bi bi-window-stack text-xl"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800 dark:text-white">OS Native Notifications</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Receive alerts directly on your desktop even when the browser is minimized.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center h-10">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" x-model="config.desktop_notifications" :checked="config.desktop_notifications == '1'" @change="toggleDesktopNotifications($event.target.checked)" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-bold text-slate-600 dark:text-slate-400" x-text="config.desktop_notifications == '1' ? 'Enabled' : 'Disabled'"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Granular Types -->
                <div x-show="config.desktop_notifications == '1'" class="p-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800">
                    <p class="text-xs font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest mb-4">Notification Types to Receive</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- All Option -->
                        <label class="flex items-center gap-3 p-4 border border-slate-100 dark:border-slate-800 rounded-2xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                            :class="isTypeSelected('all') ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : ''">
                            <input type="checkbox" @change="toggleType('all')" :checked="isTypeSelected('all')" class="sr-only">
                            <div class="w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-all" :class="isTypeSelected('all') ? 'bg-blue-600 border-blue-600' : 'border-slate-300'">
                                <i class="bi bi-check-lg text-white text-xs" x-show="isTypeSelected('all')"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">All</span>
                        </label>

                        <!-- Task -->
                        <label class="flex items-center gap-3 p-4 border border-slate-100 dark:border-slate-800 rounded-2xl cursor-pointer transition-colors"
                            :class="[isTypeSelected('task') ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : '', isTypeSelected('all') ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50 dark:hover:bg-slate-800']">
                            <input type="checkbox" @change="toggleType('task')" :checked="isTypeSelected('task')" :disabled="isTypeSelected('all')" class="sr-only">
                            <div class="w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-all" :class="isTypeSelected('task') ? 'bg-blue-600 border-blue-600' : 'border-slate-300'">
                                <i class="bi bi-check-lg text-white text-xs" x-show="isTypeSelected('task')"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Task</span>
                        </label>

                        <!-- Equipment -->
                        <label class="flex items-center gap-3 p-4 border border-slate-100 dark:border-slate-800 rounded-2xl cursor-pointer transition-colors"
                            :class="[isTypeSelected('equipment') ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : '', isTypeSelected('all') ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50 dark:hover:bg-slate-800']">
                            <input type="checkbox" @change="toggleType('equipment')" :checked="isTypeSelected('equipment')" :disabled="isTypeSelected('all')" class="sr-only">
                            <div class="w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-all" :class="isTypeSelected('equipment') ? 'bg-blue-600 border-blue-600' : 'border-slate-300'">
                                <i class="bi bi-check-lg text-white text-xs" x-show="isTypeSelected('equipment')"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Equipment</span>
                        </label>

                        <!-- Network -->
                        <label class="flex items-center gap-3 p-4 border border-slate-100 dark:border-slate-800 rounded-2xl cursor-pointer transition-colors"
                            :class="[isTypeSelected('network') ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : '', isTypeSelected('all') ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50 dark:hover:bg-slate-800']">
                            <input type="checkbox" @change="toggleType('network')" :checked="isTypeSelected('network')" :disabled="isTypeSelected('all')" class="sr-only">
                            <div class="w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-all" :class="isTypeSelected('network') ? 'bg-blue-600 border-blue-600' : 'border-slate-300'">
                                <i class="bi bi-check-lg text-white text-xs" x-show="isTypeSelected('network')"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Network</span>
                        </label>

                        <!-- Warranty -->
                        <label class="flex items-center gap-3 p-4 border border-slate-100 dark:border-slate-800 rounded-2xl cursor-pointer transition-colors"
                            :class="[isTypeSelected('warranty') ? 'bg-blue-50/50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800' : '', isTypeSelected('all') ? 'opacity-50 cursor-not-allowed' : 'hover:bg-slate-50 dark:hover:bg-slate-800']">
                            <input type="checkbox" @change="toggleType('warranty')" :checked="isTypeSelected('warranty')" :disabled="isTypeSelected('all')" class="sr-only">
                            <div class="w-5 h-5 rounded-lg border-2 flex items-center justify-center transition-all" :class="isTypeSelected('warranty') ? 'bg-blue-600 border-blue-600' : 'border-slate-300'">
                                <i class="bi bi-check-lg text-white text-xs" x-show="isTypeSelected('warranty')"></i>
                            </div>
                            <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Warranty</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function preferencesPage(initialData) {
    return {
        config: initialData,
        loading: false,
        isTypeSelected(type) {
            const types = this.config.notification_types || [];
            return types.includes(type);
        },
        toggleType(type) {
            let types = [...(this.config.notification_types || [])];
            if (type === 'all') {
                if (types.includes('all')) {
                    types = [];
                } else {
                    types = ['all'];
                }
            } else {
                if (types.includes(type)) {
                    types = types.filter(t => t !== type);
                } else {
                    types.push(type);
                }
            }
            this.config.notification_types = types;
        },
        async toggleDesktopNotifications(enabled) {
            if (enabled) {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') {
                    this.config.desktop_notifications = 0;
                    Alpine.store('app').addToast('Permission Denied', 'Browser notification permission was not granted.', 'warning');
                    return;
                }
                // When enabled, set 'all' as default
                this.config.notification_types = ['all'];
            }
            this.config.desktop_notifications = enabled ? 1 : 0;
        },
        async savePreferences() {
            this.loading = true;
            try {
                const response = await fetch('index.php?route=preferences_save', {
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
                    // Update global app store for immediate UI feedback
                    if (this.config.theme === 'dark') {
                        document.documentElement.classList.add('dark');
                        localStorage.setItem('theme', 'dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                        localStorage.setItem('theme', 'light');
                    }
                    // Update toast position in global store
                    Alpine.store('app').toastConfig.position = this.config.toast_position;
                    // Update clock and notification store
                    Alpine.store('app').timezone = this.config.timezone;
                    Alpine.store('app').timeFormat = this.config.time_format;
                    Alpine.store('app').notifications.enabled = (this.config.desktop_notifications == 1);
                    Alpine.store('app').notifications.types = this.config.notification_types;
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                console.error(e);
                Alpine.store('app').addToast('Error', 'Failed to save preferences.', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
