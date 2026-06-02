<?php
/** @var array $data */
$equipments = $data['equipments'];
$types = $data['types'];
$filters = $data['filters'];
$currentPage = $data['currentPage'];
$totalPages = $data['pages'];

$statusColors = [
    'In Use' => 'bg-green-100 text-green-700',
    'Available' => 'bg-blue-100 text-blue-700',
    'Under Repair' => 'bg-orange-100 text-orange-700',
    'Retired' => 'bg-slate-100 text-slate-700',
    'Lost/Stolen' => 'bg-red-100 text-red-700'
];
?>

<div class="space-y-8" x-data="equipmentList()">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white">Equipment Inventory</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">Manage and track all company hardware assets.</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Bulk Actions -->
            <div x-show="selectedIds.length > 0" x-cloak x-transition class="flex items-center gap-2 pr-4 border-r border-slate-200 dark:border-slate-800">
                <span class="text-xs font-bold text-blue-600" x-text="selectedIds.length + ' selected'"></span>
                <button @click="openQrModal()" class="p-2.5 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100 transition-all" title="Print QR Codes">
                    <i class="bi bi-qr-code"></i>
                </button>
                <button @click="bulkDelete" class="p-2.5 bg-red-50 text-red-600 rounded-xl hover:bg-red-100 transition-all">
                    <i class="bi bi-trash3-fill"></i>
                </button>
            </div>

            <button @click="openColumnModal" class="p-2.5 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl text-slate-500 hover:text-blue-600 transition-all shadow-sm">
                <i class="bi bi-layout-three-columns"></i>
            </button>
            
            <!-- Export Button -->
            <button @click="exportData" 
                    x-show="filters.type_id"
                    class="p-2.5 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl text-slate-500 hover:text-emerald-600 transition-all shadow-sm"
                    title="Export to CSV">
                <i class="bi bi-download"></i>
            </button>

            <div class="h-8 w-[1px] bg-slate-200 dark:bg-slate-800 hidden md:block"></div>
            <a href="index.php?route=add_equipment" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center">
                <i class="bi bi-plus-lg mr-2"></i> Add Equipment
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm transition-colors">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="relative">
                <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" x-model="filters.search" @input.debounce.500ms="applyFilters(1)" placeholder="Search serial, brand, model..." 
                    class="w-full pl-11 pr-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
            </div>
            <select x-model="filters.type_id" @change="applyFilters(1)" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                <option value="">All Types</option>
                <?php foreach($types as $t): ?>
                    <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select x-model="filters.status" @change="applyFilters(1)" class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                <option value="">All Status</option>
                <?php foreach(array_keys($statusColors) as $s): ?>
                    <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                <?php endforeach; ?>
            </select>
            <button @click="clearFilters" class="text-sm font-bold text-slate-500 hover:text-red-600 transition-colors">Clear All Filters</button>
        </div>
    </div>

    <!-- Data Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-6 py-4 w-10">
                            <input type="checkbox" @change="toggleAll" :checked="allSelected" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <template x-for="col in visibleColumns" :key="col.id">
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest cursor-pointer hover:text-blue-600 transition-colors">
                                <span x-text="col.label"></span>
                            </th>
                        </template>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800" id="equipment-table-body">
                    <?php require __DIR__ . '/partial_list.php'; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="mt-8 bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex items-center justify-between px-6 py-4 transition-colors">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing page <span class="font-bold text-slate-800 dark:text-slate-100" x-text="currentPage"></span> of <span class="font-bold text-slate-800 dark:text-slate-100" x-text="totalPages"></span> (<span x-text="totalItems"></span> items)
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

    <!-- Column Customization Modal -->
    <template x-teleport="body">
        <div x-show="showColumnModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <!-- Backdrop -->
                <div x-show="showColumnModal" 
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-slate-900/60" 
                     @click="showColumnModal = false" 
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Content -->
                <div x-show="showColumnModal"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-slate-900 w-full max-w-2xl rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transform transition-all sm:my-8 sm:align-middle">
                    
                    <div class="px-8 py-6 bg-slate-800 text-white flex justify-between items-center">
                        <h3 class="text-lg font-bold">Customize Columns</h3>
                        <button @click="showColumnModal = false" class="text-slate-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-2">
                        <template x-for="col in allColumns" :key="col.id">
                            <label class="flex items-center gap-3 p-3 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl cursor-pointer transition-colors">
                                <input type="checkbox" :checked="col.visible" @change="toggleColumn(col.id)" class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200" x-text="col.label"></span>
                            </label>
                        </template>
                    </div>
                    <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <button @click="showColumnModal = false" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- QR Code Generator Modal -->
    <template x-teleport="body">
        <div x-show="showQrModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <div x-show="showQrModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60" @click="showQrModal = false"></div>

                <div x-show="showQrModal" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-900 w-full max-w-xl rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transform transition-all sm:my-8 sm:align-middle text-left">
                    <div class="px-8 py-6 bg-blue-600 text-white flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <i class="bi bi-qr-code text-2xl"></i>
                            <h3 class="text-lg font-bold uppercase tracking-wider">QR Code Settings</h3>
                        </div>
                        <button @click="showQrModal = false" class="text-blue-200 hover:text-white transition-colors"><i class="bi bi-x-lg"></i></button>
                    </div>
                    
                    <div class="p-8 space-y-6">
                        <!-- Content Selection -->
                        <div class="space-y-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">QR Content Type</p>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative flex flex-col p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 cursor-pointer transition-all" :class="qrSettings.content === 'basic' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : 'border-transparent'">
                                    <input type="radio" x-model="qrSettings.content" value="basic" class="sr-only">
                                    <span class="text-sm font-bold text-slate-800 dark:text-white">Basic Info</span>
                                    <span class="text-[10px] text-slate-500 mt-1">Name, S/N, Model</span>
                                </label>
                                <label class="relative flex flex-col p-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 cursor-pointer transition-all" :class="qrSettings.content === 'full' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : 'border-transparent'">
                                    <input type="radio" x-model="qrSettings.content" value="full" class="sr-only">
                                    <span class="text-sm font-bold text-slate-800 dark:text-white">Full Details</span>
                                    <span class="text-[10px] text-slate-500 mt-1">+ Network & Location</span>
                                </label>
                            </div>
                        </div>

                        <!-- Size Selection -->
                        <div class="space-y-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Label Size (Physical)</p>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <label class="flex flex-col items-center justify-center p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 cursor-pointer transition-all" :class="qrSettings.size === '0.5in' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : 'border-transparent'">
                                    <input type="radio" x-model="qrSettings.size" value="0.5in" class="sr-only">
                                    <span class="text-sm font-bold text-slate-800 dark:text-white">0.5"</span>
                                    <span class="text-[9px] text-slate-500">Tiny</span>
                                </label>
                                <label class="flex flex-col items-center justify-center p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 cursor-pointer transition-all" :class="qrSettings.size === '1in' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : 'border-transparent'">
                                    <input type="radio" x-model="qrSettings.size" value="1in" class="sr-only">
                                    <span class="text-sm font-bold text-slate-800 dark:text-white">1.0"</span>
                                    <span class="text-[9px] text-slate-500">Standard</span>
                                </label>
                                <label class="flex flex-col items-center justify-center p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 cursor-pointer transition-all" :class="qrSettings.size === '1.5in' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : 'border-transparent'">
                                    <input type="radio" x-model="qrSettings.size" value="1.5in" class="sr-only">
                                    <span class="text-sm font-bold text-slate-800 dark:text-white">1.5"</span>
                                    <span class="text-[9px] text-slate-500">Large</span>
                                </label>
                                <label class="flex flex-col items-center justify-center p-3 bg-slate-50 dark:bg-slate-800 rounded-2xl border-2 cursor-pointer transition-all" :class="qrSettings.size === '2in' ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/10' : 'border-transparent'">
                                    <input type="radio" x-model="qrSettings.size" value="2in" class="sr-only">
                                    <span class="text-sm font-bold text-slate-800 dark:text-white">2.0"</span>
                                    <span class="text-[9px] text-slate-500">Extra Large</span>
                                </label>
                            </div>
                        </div>

                        <!-- Additional Options -->
                        <div class="space-y-3">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Additional Data</p>
                            <div class="flex flex-col gap-2">
                                <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors">
                                    <input type="checkbox" x-model="qrSettings.includeStatus" class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Include Current Status</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 transition-colors">
                                    <input type="checkbox" x-model="qrSettings.includeLink" class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Include Direct System Link</span>
                                </label>
                            </div>
                        </div>

                        <!-- Scan Preview Message -->
                        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-2xl border border-amber-100 dark:border-amber-900/30 flex items-start gap-3">
                            <i class="bi bi-exclamation-triangle-fill text-amber-500 mt-0.5"></i>
                            <p class="text-[10px] text-amber-800 dark:text-amber-400 font-medium leading-relaxed">
                                Tip: Small 0.5" labels are best for Basic Info only. Full Details on small labels might be difficult to scan with some cameras.
                            </p>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <button @click="showQrModal = false" class="px-6 py-2.5 text-slate-600 dark:text-slate-400 font-bold text-sm">Cancel</button>
                        <button @click="generateAndPrintQrs()" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 flex items-center gap-2">
                            <i class="bi bi-printer-fill"></i> Generate & Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<!-- QR Code Library -->
<script src="fallback/js/qrcode.min.js"></script>

<script>
function equipmentList() {
    return {
        showColumnModal: false,
        showQrModal: false,
        qrSettings: {
            content: 'basic',
            size: '1in',
            includeStatus: true,
            includeLink: true
        },
        qrIds: [],
        filters: <?php echo json_encode($filters); ?>,
        currentPage: <?php echo (int)$currentPage; ?>,
        totalPages: <?php echo (int)$totalPages; ?>,
        totalItems: <?php echo (int)$data['total']; ?>,
        selectedIds: [],
        allColumns: [
            { id: 'type', label: 'Type/Category', visible: true },
            { id: 'name', label: 'Label', visible: true },
            { id: 'brand_model', label: 'Brand & Model', visible: true },
            { id: 'status', label: 'Status', visible: true },
            { id: 'office_location', label: 'Office Location', visible: false },
            { id: 'floor', label: 'Floor', visible: false },
            { id: 'department', label: 'Department / Room', visible: true },
            { id: 'assigned_to', label: 'Assigned To', visible: false },
            { id: 'condition', label: 'Condition', visible: true },
            { id: 'network', label: 'Network Info', visible: true },
            { id: 'warranty', label: 'Warranty Status', visible: false },
            { id: 'created_at', label: 'Date Added', visible: false }
        ],
        init() {
            const saved = localStorage.getItem('equipment_columns');
            if (saved) {
                const prefs = JSON.parse(saved);
                this.allColumns.forEach(col => {
                    if (prefs[col.id] !== undefined) col.visible = prefs[col.id];
                });
            }
        },
        get visibleColumns() {
            return this.allColumns.filter(c => c.visible);
        },
        get allSelected() {
            return this.selectedIds.length > 0 && document.querySelectorAll('#equipment-table-body tr:not(.text-center):not(.hidden)').length === this.selectedIds.length;
        },
        toggleAll() {
            if (this.allSelected) {
                this.selectedIds = [];
            } else {
                const ids = [];
                document.querySelectorAll('#equipment-table-body input[type="checkbox"]').forEach(cb => {
                    ids.push(parseInt(cb.value));
                });
                this.selectedIds = ids;
            }
        },
        toggleColumn(id) {
            const col = this.allColumns.find(c => c.id === id);
            if (col) col.visible = !col.visible;
            const prefs = {};
            this.allColumns.forEach(c => prefs[c.id] = c.visible);
            localStorage.setItem('equipment_columns', JSON.stringify(prefs));
        },
        async applyFilters(page = 1) {
            this.currentPage = page;
            this.selectedIds = []; // Clear selection when filters change
            const params = new URLSearchParams();
            Object.keys(this.filters).forEach(k => {
                if (this.filters[k]) params.set(k, this.filters[k]);
            });
            params.set('page', page);

            try {
                const response = await fetch('index.php?route=list_equipment&' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await response.text();
                const container = document.getElementById('equipment-table-body');
                container.innerHTML = html;
                
                // Re-initialize Alpine for new content
                if (window.Alpine) {
                    Alpine.process(container);
                }
                
                // Sync pagination state from hidden meta div in the partial
                const meta = document.getElementById('equipment-pagination-meta');
                if (meta) {
                    this.totalPages = parseInt(meta.getAttribute('data-pages') || '1');
                    // Prioritize the page we actually requested to avoid flickering or race conditions
                    this.currentPage = page; 
                    this.totalItems = parseInt(meta.getAttribute('data-total') || '0');
                }

                // Update URL without refresh
                const newUrl = window.location.pathname + '?route=list_equipment&' + params.toString();
                window.history.pushState({ path: newUrl }, '', newUrl);
            } catch (e) {
                console.error('Filter failed', e);
            }
        },
        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.applyFilters(page);
        },
        clearFilters() {
            this.filters = { type_id: '', status: '', search: '' };
            this.applyFilters(1);
        },
        openColumnModal() {
            this.showColumnModal = true;
        },
        openQrModal(id = null) {
            this.qrIds = id ? [id] : this.selectedIds;
            if (this.qrIds.length === 0) {
                Alpine.store('app').addToast('Warning', 'Please select at least one item.', 'warning');
                return;
            }
            this.showQrModal = true;
        },
        async generateAndPrintQrs() {
            const ids = this.qrIds.join(',');
            try {
                const response = await fetch(`index.php?route=equipment_qr_data&ids=${ids}`);
                const data = await response.json();
                
                if (data.length === 0) return;

                const printWindow = window.open('', '_blank');
                const size = this.qrSettings.size;
                const contentType = this.qrSettings.content;
                const includeStatus = this.qrSettings.includeStatus;
                const includeLink = this.qrSettings.includeLink;
                
                // Construct base URL for direct links
                const baseUrl = window.location.origin + window.location.pathname;

                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Equipment QR Codes</title>
                        <script src="fallback/js/qrcode.min.js"><\/script>
                        <style>
                            body { font-family: sans-serif; margin: 0; padding: 0.5in; }
                            .page-grid { 
                                display: grid; 
                                grid-template-columns: repeat(auto-fill, ${size}); 
                                gap: 0.4in;
                                justify-content: center;
                            }
                            .qr-item { 
                                width: ${size}; 
                                height: auto; 
                                display: flex; 
                                flex-direction: column; 
                                align-items: center; 
                                text-align: center;
                                page-break-inside: avoid;
                            }
                            .qr-container { 
                                width: ${size}; 
                                height: ${size}; 
                            }
                            .qr-container img { width: 100%; height: 100%; }
                            .label-text { 
                                margin-top: 5px; 
                                font-size: 8px; 
                                font-weight: bold; 
                                line-height: 1.2;
                                word-break: break-all;
                                max-width: 100%;
                            }
                            @media print {
                                @page { margin: 0.5in; }
                                body { padding: 0; }
                                .no-print { display: none; }
                            }
                        </style>
                    </head>
                    <body>
                        <div class="no-print" style="margin-bottom: 20px; text-align: center;">
                            <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Now</button>
                        </div>
                        <div class="page-grid" id="qr-grid"></div>
                        <script>
                            const data = ${JSON.stringify(data)};
                            const grid = document.getElementById('qr-grid');
                            
                            data.forEach(item => {
                                let lines = [];
                                
                                // Equipment Info First
                                if ("${contentType}" === "basic") {
                                    lines.push("NAME: " + item.name);
                                    lines.push("S/N: " + (item.serial_number || "N/A"));
                                    lines.push("MOD: " + (item.model || "N/A"));
                                } else {
                                    lines.push("NAME: " + item.name);
                                    lines.push("S/N: " + (item.serial_number || "N/A"));
                                    lines.push("IP: " + (item.ip_address || "N/A"));
                                    lines.push("MAC: " + (item.mac_address || "N/A"));
                                    lines.push("LOC: " + (item.location || "N/A"));
                                }

                                if (${includeStatus}) {
                                    lines.push("STATUS: " + item.status);
                                }

                                // Direct Link at the bottom
                                if (${includeLink}) {
                                    lines.push(""); // Add an empty line for spacing
                                    lines.push("VIEW ONLINE:");
                                    lines.push("${baseUrl}?route=view_equipment&id=" + item.id);
                                }

                                const content = lines.join("\\n");

                                const div = document.createElement('div');
                                div.className = 'qr-item';
                                
                                const qrContainer = document.createElement('div');
                                qrContainer.className = 'qr-container';
                                qrContainer.id = 'qr-' + item.id;
                                
                                const labelText = document.createElement('div');
                                labelText.className = 'label-text';
                                labelText.innerText = item.name + (item.serial_number ? " (" + item.serial_number + ")" : "");
                                
                                div.appendChild(qrContainer);
                                div.appendChild(labelText);
                                grid.appendChild(div);

                                // Get actual pixels for the QR code
                                const px = parseFloat("${size}") * 96; 
                                new QRCode(qrContainer, {
                                    text: content,
                                    width: px,
                                    height: px,
                                    colorDark : "#000000",
                                    colorLight : "#ffffff",
                                    correctLevel : QRCode.CorrectLevel.M
                                });
                            });

                            window.onload = () => {
                                // Short delay to ensure QR codes are rendered before print dialog
                                setTimeout(() => {
                                    // window.print();
                                }, 1000);
                            };
                        <\/script>
                    </body>
                    </html>
                `);
                printWindow.document.close();
                this.showQrModal = false;

            } catch (e) {
                console.error('QR Generation failed', e);
                Alpine.store('app').addToast('Error', 'Failed to generate QR codes.', 'error');
            }
        },
        exportData() {
            const params = new URLSearchParams();
            if (this.filters.type_id) params.set('type_id', this.filters.type_id);
            if (this.selectedIds.length > 0) params.set('ids', this.selectedIds.join(','));
            
            window.location.href = 'index.php?route=equipment_export&' + params.toString();
        },
        async deleteItem(id, label) {
            Alpine.store('app').confirm('Delete Asset', `Are you sure you want to delete asset ${label}?`, async () => {
                try {
                    const response = await fetch(`index.php?route=equipment_delete&id=${id}`);
                    const result = await response.json();
                    if (result.success) {
                        Alpine.store('app').addToast('Deleted', result.message, 'success');
                        this.applyFilters(this.currentPage);
                    } else {
                        Alpine.store('app').addToast('Error', result.message, 'error');
                    }
                } catch (e) {
                    Alpine.store('app').addToast('Error', 'Failed to delete.', 'error');
                }
            });
        },
        async bulkDelete() {
            Alpine.store('app').confirm('Bulk Delete', `Are you sure you want to delete ${this.selectedIds.length} items?`, async () => {
                try {
                    const response = await fetch('index.php?route=equipment_bulk_delete', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            ids: this.selectedIds,
                            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        Alpine.store('app').addToast('Deleted', result.message, 'success');
                        this.selectedIds = [];
                        this.applyFilters(this.currentPage);
                    } else {
                        Alpine.store('app').addToast('Error', result.message, 'error');
                    }
                } catch (e) {
                    Alpine.store('app').addToast('Error', 'Bulk deletion failed.', 'error');
                }
            });
        }
    }
}
</script>
