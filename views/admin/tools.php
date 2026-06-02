<?php
/** @var array $data */
?>

<div class="max-w-4xl mx-auto space-y-8" x-data="toolsPage()">
    <div>
        <p class="text-sm text-slate-500 dark:text-slate-400">Perform critical database maintenance and system health checks.</p>
    </div>

    <div class="grid grid-cols-1 gap-8">
        <!-- Maintenance Tools -->
        <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 transition-colors">
            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-8 flex items-center">
                <i class="bi bi-tools mr-2"></i> Database & System Maintenance
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Database Backup -->
                <a href="index.php?route=admin_backup_db" class="p-6 bg-blue-50 dark:bg-blue-900/10 rounded-2xl border border-blue-100 dark:border-blue-900/30 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="bi bi-database-down"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Database Backup</p>
                    <p class="text-[10px] text-slate-500 mt-1">Download full SQL export for safe storage.</p>
                </a>

                <!-- Optimize DB -->
                <button @click="runTool('optimize')" class="p-6 bg-emerald-50 dark:bg-emerald-900/10 rounded-2xl border border-emerald-100 dark:border-emerald-900/30 hover:shadow-md transition-all group text-left">
                    <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Optimize DB</p>
                    <p class="text-[10px] text-slate-500 mt-1">Remove overhead and re-index tables for performance.</p>
                </button>

                <!-- Cache Cleanup -->
                <button @click="runTool('cleanup')" class="p-6 bg-purple-50 dark:bg-purple-900/10 rounded-2xl border border-purple-100 dark:border-purple-900/30 hover:shadow-md transition-all group text-left">
                    <div class="w-10 h-10 bg-purple-600 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="bi bi-trash3"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">Cleanup Cache</p>
                    <p class="text-[10px] text-slate-500 mt-1">Clear temporary system files and backups.</p>
                </button>

                <!-- System Settings -->
                <a href="index.php?route=settings" class="p-6 bg-orange-50 dark:bg-orange-900/10 rounded-2xl border border-orange-100 dark:border-orange-900/30 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-orange-600 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="bi bi-gear-wide-connected"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">System Settings</p>
                    <p class="text-[10px] text-slate-500 mt-1">Configure global application behavior and preferences.</p>
                </a>

                <!-- System Logs -->
                <a href="index.php?route=admin_logs" class="p-6 bg-slate-50 dark:bg-slate-800 rounded-2xl border border-slate-100 dark:border-slate-700 hover:shadow-md transition-all group">
                    <div class="w-10 h-10 bg-slate-800 dark:bg-slate-700 rounded-xl flex items-center justify-center text-white mb-4 group-hover:scale-110 transition-transform">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100">System Logs</p>
                    <p class="text-[10px] text-slate-500 mt-1">Monitor all system activities and errors.</p>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function toolsPage() {
    return {
        loading: false,
        async runTool(type) {
            const routes = {
                'optimize': 'admin_optimize_db',
                'cleanup': 'admin_cleanup_cache'
            };
            
            this.loading = true;
            Alpine.store('app').addToast('Running', 'Tool execution started...', 'info');

            try {
                const response = await fetch(`index.php?route=${routes[type]}`);
                const result = await response.json();
                if (result.success) {
                    Alpine.store('app').addToast('Success', result.message, 'success');
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Maintenance tool failed.', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>