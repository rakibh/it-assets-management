<?php
/** @var array $data */
$blocks = $data['blocks'];
?>

<div class="max-w-6xl mx-auto space-y-8" x-data="blockManager()">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Predefined Blocks</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Design reusable groups of fields to standardise data entry across different equipment types.</p>
        </div>
        <button @click="openAddModal" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-bold shadow-xl shadow-blue-500/20 transition-all flex items-center gap-2">
            <i class="bi bi-plus-lg"></i> Create New Block
        </button>
    </div>

    <!-- Blocks Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($blocks as $block): ?>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl font-black">
                        <i class="bi bi-stack"></i>
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openEditModal(<?php echo htmlspecialchars(json_encode($block)); ?>)" class="p-2 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-blue-600 rounded-xl transition-colors">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button @click="deleteBlock(<?php echo $block['id']; ?>)" class="p-2 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-600 rounded-xl transition-colors">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1"><?php echo htmlspecialchars($block['name']); ?></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-6"><?php echo htmlspecialchars($block['description'] ?: 'No description provided.'); ?></p>
                
                <div class="pt-4 border-t border-slate-50 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                        <?php echo count(json_decode($block['schema'], true)); ?> Fields Defined
                    </span>
                    <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-lg text-[9px] font-black uppercase tracking-tighter">System ID: #<?php echo $block['id']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($blocks)): ?>
            <div class="col-span-full py-20 text-center bg-white dark:bg-slate-900 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-800">
                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <i class="bi bi-stack text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">No blocks defined yet</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 max-w-xs mx-auto mt-1">Start by creating a block to define reusable field groups for your equipment inventory.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal -->
    <template x-teleport="body">
        <div x-show="showModal" x-cloak class="fixed inset-0 z-[100] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
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
                     class="inline-block align-bottom bg-white dark:bg-slate-900 w-full max-w-4xl rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transform transition-all sm:my-8 sm:align-middle flex flex-col max-h-[90vh]">
                    
                    <div class="px-8 py-6 bg-slate-800 text-white flex justify-between items-center">
                        <h3 class="text-lg font-bold" x-text="modalMode === 'add' ? 'Add Predefined Block' : 'Edit Block'"></h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                        <div class="space-y-8">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Block Name</label>
                                    <input type="text" x-model="formData.name" placeholder="e.g. Memory Specification"
                                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Description</label>
                                    <input type="text" x-model="formData.description" placeholder="Short explanation of this block..."
                                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                                </div>
                            </div>

                            <!-- Form Builder -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Fields Configuration</h4>
                                    <button @click="addField" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1 transition-colors">
                                        <i class="bi bi-plus-circle-fill"></i> Add Field
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="(field, index) in formData.fields" :key="index">
                                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-start gap-4 group/field">
                                            <div class="flex-shrink-0 pt-2.5 text-slate-300 dark:text-slate-700 font-black italic text-sm" x-text="index + 1"></div>
                                            
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-4">
                                                <!-- Field Name -->
                                                <div class="md:col-span-4 space-y-1">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Field Name</label>
                                                    <input type="text" x-model="field.name" @input="updateFieldName(field)" placeholder="e.g. RAM Size"
                                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold outline-none focus:ring-1 focus:ring-blue-500 dark:text-slate-100">
                                                </div>

                                                <!-- Field Type -->
                                                <div class="md:col-span-3 space-y-1">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Type</label>
                                                    <select x-model="field.type"
                                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold outline-none focus:ring-1 focus:ring-blue-500 dark:text-slate-100">
                                                        <option value="text">Text</option>
                                                        <option value="number">Number</option>
                                                        <option value="date">Date</option>
                                                        <option value="select">Dropdown</option>
                                                        <option value="radio">Radio</option>
                                                        <option value="checkbox">Checkbox (Multi)</option>
                                                        <option value="textarea">Large Text</option>
                                                    </select>
                                                </div>

                                                <!-- Width -->
                                                <div class="md:col-span-2 space-y-1">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Width</label>
                                                    <select x-model="field.width"
                                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold outline-none focus:ring-1 focus:ring-blue-500 dark:text-slate-100">
                                                        <option value="100%">100%</option>
                                                        <option value="50%">50%</option>
                                                        <option value="33%">33%</option>
                                                    </select>
                                                </div>

                                                <!-- Options (Only for select/radio/checkbox) -->
                                                <div class="md:col-span-3 space-y-1" x-show="['select', 'radio', 'checkbox'].includes(field.type)">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase tracking-tighter">Options (comma separated)</label>
                                                    <input type="text" x-model="field.options_str" @input="updateFieldOptions(field)" placeholder="e.g. 8GB, 16GB, 32GB"
                                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold outline-none focus:ring-1 focus:ring-blue-500 dark:text-slate-100">
                                                </div>

                                                <!-- Settings (Required, Unique, etc) -->
                                                <div class="md:col-span-3 flex items-center gap-4 pt-5" x-show="!['select', 'radio', 'checkbox'].includes(field.type)">
                                                    <label class="flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" x-model="field.required" class="w-3 h-3 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                                        <span class="text-[10px] font-bold text-slate-500 uppercase">Required</span>
                                                    </label>
                                                </div>
                                            </div>

                                            <button @click="removeField(index)" class="mt-7 p-1.5 text-slate-300 hover:text-rose-500 transition-colors">
                                                <i class="bi bi-trash3 text-sm"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <button @click="showModal = false" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">Cancel</button>
                        <button @click="saveBlock" :disabled="loading" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
                            <span x-show="!loading" x-text="modalMode === 'add' ? 'Create Block' : 'Update Block'"></span>
                            <span x-show="loading"><i class="bi bi-arrow-repeat animate-spin"></i> Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function blockManager() {
    return {
        showModal: false,
        modalMode: 'add',
        loading: false,
        formData: {
            id: '',
            name: '',
            description: '',
            fields: []
        },
        openAddModal() {
            this.modalMode = 'add';
            this.formData = { id: '', name: '', description: '', fields: [] };
            this.showModal = true;
        },
        openEditModal(block) {
            this.modalMode = 'edit';
            const fields = JSON.parse(block.schema || '[]');
            fields.forEach(f => {
                if (f.options) f.options_str = f.options.join(', ');
            });
            this.formData = { ...block, fields };
            this.showModal = true;
        },
        addField() {
            this.formData.fields.push({
                name: '',
                label: '',
                type: 'text',
                width: '100%',
                required: false,
                options: [],
                options_str: ''
            });
        },
        removeField(index) {
            this.formData.fields.splice(index, 1);
        },
        updateFieldName(field) {
            field.label = field.name;
        },
        updateFieldOptions(field) {
            field.options = field.options_str.split(',').map(o => o.trim()).filter(o => o !== '');
        },
        async saveBlock() {
            if (!this.formData.name) {
                Alpine.store('app').addToast('Error', 'Block name is required.', 'error');
                return;
            }
            this.loading = true;
            try {
                const response = await fetch('index.php?route=equipment_save_block', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        id: this.formData.id,
                        name: this.formData.name,
                        description: this.formData.description,
                        schema: JSON.stringify(this.formData.fields)
                    })
                });
                const result = await response.json();
                if (result.success) {
                    Alpine.store('app').addToast('Success', result.message, 'success');
                    window.location.reload();
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'An unexpected error occurred.', 'error');
            } finally {
                this.loading = false;
            }
        },
        async deleteBlock(id) {
            Alpine.store('app').confirm('Delete Block', 'Are you sure you want to delete this block? This may affect equipment types using it.', async () => {
                try {
                    const response = await fetch(`index.php?route=equipment_delete_block&id=${id}`);
                    const result = await response.json();
                    if (result.success) {
                        Alpine.store('app').addToast('Success', result.message, 'success');
                        window.location.reload();
                    } else {
                        Alpine.store('app').addToast('Error', result.message, 'error');
                    }
                } catch (e) {
                    Alpine.store('app').addToast('Error', 'Failed to delete block.', 'error');
                }
            });
        }
    }
}
</script>
