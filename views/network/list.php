<?php
/** @var array $data */
$networks = $data['networks'];
$unassignedEquip = $data['unassigned_equipment'];
$filters = $data['filters'];
$currentPage = $data['current_page'];
$totalPages = $data['pages'];
$sortBy = $data['sort_by'] ?? 'created_at';
$sortDir = $data['sort_dir'] ?? 'DESC';
?>

<div x-data="networkManagement()">
    <!-- Header & Actions -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage IP addresses, MACs, and equipment mapping.</p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="exportNetwork" class="px-4 py-2.5 text-sm font-bold text-slate-600 dark:text-slate-400 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl hover:bg-slate-50 transition-all shadow-sm flex items-center">
                <i class="bi bi-download mr-2"></i> Export CSV
            </button>
            <button @click="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold flex items-center shadow-lg shadow-blue-500/20 transition-all">
                <i class="bi bi-plus-lg mr-2"></i> Add Network Info
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm mb-8 flex flex-wrap items-center gap-4 transition-colors duration-300">
        <div class="relative flex-1 min-w-[240px]">
            <i class="bi bi-search absolute left-3 top-2.5 text-slate-400"></i>
            <input type="text" x-model="filters.search" @input.debounce.500ms="applyFilters" 
                placeholder="Search IP, MAC, Cable, Equipment..." 
                class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none dark:text-slate-200">
        </div>
        <select x-model="filters.status" @change="applyFilters" 
            class="px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none dark:text-slate-200">
            <option value="">All Status</option>
            <option value="assigned">Assigned</option>
            <option value="unassigned">Unassigned</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">
                    <tr>
                        <th class="px-6 py-4">#</th>
                        <th class="px-6 py-4 cursor-pointer hover:text-blue-600 transition-colors" @click="sort('ip_address')">
                            <div class="flex items-center gap-1">
                                IP Address
                                <i class="bi" :class="getSortIcon('ip_address')"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4">Cable</th>
                        <th class="px-6 py-4 cursor-pointer hover:text-blue-600 transition-colors" @click="sort('patch_panel_no')">
                            <div class="flex items-center gap-1">
                                Patch Panel
                                <i class="bi" :class="getSortIcon('patch_panel_no')"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 cursor-pointer hover:text-blue-600 transition-colors" @click="sort('switch_no')">
                            <div class="flex items-center gap-1">
                                Network Switch
                                <i class="bi" :class="getSortIcon('switch_no')"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4">Attached Equipment</th>
                        <th class="px-6 py-4 cursor-pointer hover:text-blue-600 transition-colors" @click="sort('status')">
                            <div class="flex items-center gap-1">
                                Status
                                <i class="bi" :class="getSortIcon('status')"></i>
                            </div>
                        </th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                    <?php 
                    $settingsRepo = new \Modules\Admin\SettingsRepository();
                    $limit = (int)$settingsRepo->get('records_per_page', 20);
                    $i = ($currentPage - 1) * $limit + 1; 
                    foreach($networks as $n): 
                    ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-5">
                                <span class="text-xs font-bold text-slate-400"><?php echo $i++; ?></span>
                            </td>
                            <td class="px-6 py-5">
                                <a href="index.php?route=view_network&id=<?php echo $n['id']; ?>" class="text-sm font-bold text-slate-800 dark:text-slate-100 hover:text-blue-600 transition-colors">
                                    <?php echo htmlspecialchars($n['ip_address'] ?? 'N/A'); ?>
                                </a>
                            </td>
                            <td class="px-6 py-5 text-sm font-bold text-slate-700 dark:text-slate-300">
                                <?php echo htmlspecialchars($n['cable_no'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-5 text-xs font-bold text-slate-500">
                                <?php echo htmlspecialchars((($n['patch_panel_no'] ?: '-') . ' / ' . ($n['patch_panel_port'] ?: '-')) ?? ''); ?>
                            </td>
                            <td class="px-6 py-5 text-xs font-bold text-slate-500">
                                <?php echo htmlspecialchars((($n['switch_no'] ?: '-') . ' / ' . ($n['switch_port'] ?: '-')) ?? ''); ?>
                            </td>
                            <td class="px-6 py-5">
                                <?php if($n['equipment_id']): ?>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars($n['type_name'] ?? ''); ?></span>
                                        <span class="text-[10px] text-slate-500"><?php echo htmlspecialchars(($n['brand'] ?? '') . ' ' . ($n['model'] ?? '')); ?> (<?php echo htmlspecialchars($n['serial_number'] ?? ''); ?>)</span>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs italic text-slate-400">Available</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5">
                                <?php if($n['equipment_id']): ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/30">Assigned</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 border border-green-100 dark:border-green-900/30">Free</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-5 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="index.php?route=view_network&id=<?php echo $n['id']; ?>" class="p-2 text-slate-400 hover:text-blue-600 transition-all rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button @click="openModal('edit', <?php echo htmlspecialchars(json_encode($n)); ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-all rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button @click="deleteNetwork(<?php echo $n['id']; ?>, '<?php echo $n['ip_address'] ?? ''; ?>', <?php echo $n['equipment_id'] ? 'true' : 'false'; ?>)" class="p-2 text-slate-400 hover:text-red-600 transition-all rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Showing page <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $currentPage; ?></span> of <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo $totalPages; ?></span> (<?php echo $data['total']; ?> nodes)
            </p>
            <div class="flex space-x-1">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="index.php?route=list_network&page=<?php echo $i; ?>&search=<?php echo $filters['search']; ?>&status=<?php echo $filters['status']; ?>" 
                       class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all <?php echo $i === $currentPage ? 'bg-blue-600 text-white shadow-md' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
<!-- Node Form Modal -->
<div x-show="showModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Backdrop -->
        <div x-show="showModal" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-slate-900/60" 
             @click="showModal = false"
             aria-hidden="true"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <!-- Modal Content -->
        <div x-show="showModal" 
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:align-middle sm:max-w-xl sm:w-full border dark:border-slate-800">
                
                <div class="bg-slate-800 dark:bg-slate-950 px-8 py-6 flex justify-between items-center text-white">
                    <div>
                        <h3 class="text-xl font-bold" x-text="formData.network_id ? 'Update Network Node' : 'Add Network Node'"></h3>
                        <p class="text-xs text-slate-400 mt-1">Configure infrastructure details and assignment.</p>
                    </div>
                    <button @click="showModal = false" class="p-2 hover:bg-white/10 rounded-xl transition-colors">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-8 space-y-6">
                    <!-- Section: Core Identifiers -->
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">IP Address</label>
                            <input type="text" x-model="formData.ip_address" placeholder="e.g. 192.168.1.50" 
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-slate-100">
                        </div>
                    </div>

                    <!-- Section: physical Path -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Cable No</label>
                            <input type="text" x-model="formData.cable_no" placeholder="Cable Label"
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:text-slate-100">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">Assign Equipment</label>
                            <select x-model="formData.equipment_id" 
                                class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:text-slate-100 font-bold">
                                <option value="">No Equipment (Free)</option>
                                <template x-if="currentAssigned">
                                    <option :value="currentAssigned.id" x-text="currentAssigned.label"></option>
                                </template>
                                <?php foreach($unassignedEquip as $e): ?>
                                    <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars(($e['type_name'] ?? '') . ' (' . ($e['serial_number'] ?? '') . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Section: Port Configuration -->
                    <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700 space-y-6">
                        <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Port Mapping</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase mb-2">Patch Panel (No / Port / Location)</label>
                                <div class="flex gap-2">
                                    <input type="text" x-model="formData.patch_panel_no" placeholder="No" class="w-20 px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs dark:text-slate-100">
                                    <input type="text" x-model="formData.patch_panel_port" placeholder="Port" class="w-20 px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs dark:text-slate-100">
                                    <input type="text" x-model="formData.patch_panel_location" placeholder="Panel Location" class="flex-1 px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs dark:text-slate-100">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[9px] font-bold text-slate-500 uppercase mb-2">Switch (No / Port / Location)</label>
                                <div class="flex gap-2">
                                    <input type="text" x-model="formData.switch_no" placeholder="No" class="w-20 px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs dark:text-slate-100">
                                    <input type="text" x-model="formData.switch_port" placeholder="Port" class="w-20 px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs dark:text-slate-100">
                                    <input type="text" x-model="formData.switch_location" placeholder="Switch Location" class="flex-1 px-3 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs dark:text-slate-100">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Remarks</label>
                        <textarea x-model="formData.remarks" rows="2" class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:text-slate-100"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200">Cancel</button>
                        <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 disabled:opacity-50 transition-all flex items-center">
                            <span x-show="!loading" x-text="formData.network_id ? 'Update Node' : 'Create Node'"></span>
                            <span x-show="loading" class="animate-spin"><i class="bi bi-arrow-repeat"></i></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function networkManagement() {
    return {
        showModal: false,
        loading: false,
        currentAssigned: null,
        filters: {
            search: '<?php echo $filters['search']; ?>',
            status: '<?php echo $filters['status']; ?>'
        },
        formData: {
            network_id: '',
            ip_address: '',
            cable_no: '',
            patch_panel_no: '',
            patch_panel_port: '',
            patch_panel_location: '',
            switch_no: '',
            switch_port: '',
            switch_location: '',
            remarks: '',
            equipment_id: '',
            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
        },
        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            if (editId) {
                const networks = <?php echo json_encode($networks); ?>;
                const node = networks.find(n => n.id == editId);
                if (node) {
                    this.openModal('edit', node);
                    // Remove edit param from URL without refreshing
                    const newUrl = window.location.pathname + '?' + urlParams.toString().replace(/&?edit=[^&]*/, '');
                    window.history.replaceState({}, '', newUrl || 'index.php?route=list_network');
                }
            }
        },
        openModal(mode, data = null) {
            if (data) {
                this.formData = {
                    network_id: data.id,
                    ip_address: data.ip_address || '',
                    cable_no: data.cable_no || '',
                    patch_panel_no: data.patch_panel_no || '',
                    patch_panel_port: data.patch_panel_port || '',
                    patch_panel_location: data.patch_panel_location || '',
                    switch_no: data.switch_no || '',
                    switch_port: data.switch_port || '',
                    switch_location: data.switch_location || '',
                    remarks: data.remarks || '',
                    equipment_id: data.equipment_id || '',
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                };
                
                if (data.equipment_id) {
                    this.currentAssigned = {
                        id: data.equipment_id,
                        label: `${data.type_name} (${data.serial_number})`
                    };
                } else {
                    this.currentAssigned = null;
                }
            } else {
                this.formData = {
                    network_id: '', ip_address: '', cable_no: '',
                    patch_panel_no: '', patch_panel_port: '', patch_panel_location: '',
                    switch_no: '', switch_port: '', switch_location: '',
                    remarks: '', equipment_id: '', csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                };
                this.currentAssigned = null;
            }
            this.showModal = true;
            
            // Re-sync equipment_id after modal renders to avoid Alpine.js binding issues
            if (data && data.equipment_id) {
                this.$nextTick(() => {
                    this.formData.equipment_id = data.equipment_id;
                });
            }
        },
        async submitForm() {
            this.loading = true;
            try {
                const response = await fetch('index.php?route=network_store', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formData)
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
                Alpine.store('app').addToast('Error', 'Network request failed.', 'error');
                this.loading = false;
            }
        },
        deleteNetwork(id, ip, isAssigned) {
            if (isAssigned) {
                Alpine.store('app').addToast('Access Denied', 'Cannot delete a network node that is currently assigned to an equipment. Unassign it first.', 'error');
                return;
            }

            Alpine.store('app').confirm(
                'Delete Network Node',
                `Are you sure you want to delete the network configuration for ${ip || 'this node'}?`,
                async () => {
                    this.loading = true;
                    try {
                        const response = await fetch('index.php?route=network_delete&id=' + id);
                        const result = await response.json();
                        if (result.success) {
                            Alpine.store('app').addToast('Deleted', result.message, 'success');
                            setTimeout(() => window.location.reload(), 1000);
                        } else {
                            Alpine.store('app').addToast('Error', result.message, 'error');
                            this.loading = false;
                        }
                    } catch (e) {
                        Alpine.store('app').addToast('Error', 'Failed to delete record.', 'error');
                        this.loading = false;
                    }
                }
            );
        },
        applyFilters() {
            const params = new URLSearchParams(window.location.search);
            params.set('route', 'list_network');
            if (this.filters.search) params.set('search', this.filters.search); else params.delete('search');
            if (this.filters.status) params.set('status', this.filters.status); else params.delete('status');
            params.set('page', '1');
            window.location.href = 'index.php?' + params.toString();
        },
        exportNetwork() {
            window.location.href = 'index.php?route=network_export';
        },
        sort(field) {
            const params = new URLSearchParams(window.location.search);
            let dir = 'ASC';
            if (params.get('sort_by') === field && params.get('sort_dir') === 'ASC') {
                dir = 'DESC';
            }
            params.set('sort_by', field);
            params.set('sort_dir', dir);
            params.set('page', '1');
            window.location.href = 'index.php?' + params.toString();
        },
        getSortIcon(field) {
            const params = new URLSearchParams(window.location.search);
            const currentField = params.get('sort_by') || 'created_at';
            const currentDir = params.get('sort_dir') || 'DESC';
            
            if (currentField !== field) return 'bi-arrow-down-up opacity-30';
            return currentDir === 'ASC' ? 'bi-sort-up text-blue-600' : 'bi-sort-down text-blue-600';
        }
    }
}
</script>
