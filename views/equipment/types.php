<?php
/** @var array $data */
$types = $data['types'];
$blocks = $data['blocks'];
?>

<div class="max-w-6xl mx-auto space-y-8" x-data="typeManager(<?php echo htmlspecialchars(json_encode($blocks)); ?>)">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Equipment Types</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Define categories and custom data structures for your assets.</p>
        </div>
        <button @click="openAddModal" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-bold shadow-xl shadow-blue-500/20 transition-all flex items-center gap-2">
            <i class="bi bi-plus-lg"></i> Create New Type
        </button>
    </div>

    <!-- Types Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($types as $type): ?>
            <div class="bg-white dark:bg-slate-900 p-6 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-xl font-black">
                        <i class="bi <?php echo htmlspecialchars($type['icon'] ?: 'bi-box'); ?>"></i>
                    </div>
                    <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button @click="openEditModal(<?php echo htmlspecialchars(json_encode($type)); ?>)" class="p-2 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-blue-600 rounded-xl transition-colors">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button @click="deleteType(<?php echo $type['id']; ?>)" class="p-2 bg-slate-50 dark:bg-slate-800 text-slate-400 hover:text-rose-600 rounded-xl transition-colors">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
                
                <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1"><?php echo htmlspecialchars($type['name']); ?></h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-2 mb-6"><?php echo htmlspecialchars($type['description'] ?: 'No description provided.'); ?></p>
                
                <div class="pt-4 border-t border-slate-50 dark:border-slate-800 flex items-center justify-between">
                    <div class="flex flex-wrap gap-1">
                        <?php 
                        $blockIds = json_decode($type['block_ids'], true) ?: [];
                        foreach($blockIds as $bid): 
                            $bname = 'Unknown';
                            foreach($blocks as $b) { if($b['id'] == $bid) { $bname = $b['name']; break; } }
                        ?>
                            <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/10 text-blue-600 dark:text-blue-400 rounded text-[9px] font-bold uppercase"><?php echo htmlspecialchars($bname); ?></span>
                        <?php endforeach; ?>
                        <?php if(empty($blockIds)): ?>
                            <span class="text-[9px] font-bold text-slate-400 uppercase italic">No blocks</span>
                        <?php endif; ?>
                    </div>
                    <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-lg text-[9px] font-black uppercase tracking-tighter">ID: #<?php echo $type['id']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($types)): ?>
            <div class="col-span-full py-20 text-center bg-white dark:bg-slate-900 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-800">
                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                    <i class="bi bi-cpu text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">No equipment types defined</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 max-w-xs mx-auto mt-1">Start by creating a type to categorize your hardware assets.</p>
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
                        <h3 class="text-lg font-bold" x-text="modalMode === 'add' ? 'Add Equipment Type' : 'Edit Type'"></h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                        <div class="space-y-8">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-1.5 md:col-span-1">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Type Name</label>
                                    <input type="text" x-model="formData.name" placeholder="e.g. Laptop, Server..."
                                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                                </div>
                                <div class="space-y-1.5 md:col-span-1">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Icon (Bootstrap)</label>
                                    <input type="text" x-model="formData.icon" placeholder="e.g. bi-laptop"
                                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                                </div>
                                <div class="space-y-1.5 md:col-span-1">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Description</label>
                                    <input type="text" x-model="formData.description" placeholder="Short summary..."
                                        class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                                </div>
                            </div>

                            <!-- Blocks Assignment -->
                            <div class="space-y-4">
                                <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">Attach Predefined Blocks</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                    <?php foreach ($blocks as $block): ?>
                                        <label class="flex items-center gap-3 p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-2xl cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-colors">
                                            <input type="checkbox" value="<?php echo $block['id']; ?>" x-model="formData.block_ids"
                                                class="w-5 h-5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                            <div>
                                                <p class="text-xs font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($block['name']); ?></p>
                                                <p class="text-[10px] text-slate-400 uppercase font-black tracking-tighter"><?php echo count(json_decode($block['schema'], true)); ?> Fields</p>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Custom Fields Builder (Type Specific) -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                                    <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Type-Specific Fields</h4>
                                    <button @click="addField" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1 transition-colors">
                                        <i class="bi bi-plus-circle-fill"></i> Add Custom Field
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    <template x-for="(field, index) in formData.fields" :key="index">
                                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-start gap-4">
                                            <div class="flex-shrink-0 pt-2.5 text-slate-300 dark:text-slate-700 font-black italic text-sm" x-text="index + 1"></div>
                                            <div class="flex-1 grid grid-cols-1 md:grid-cols-12 gap-4">
                                                <div class="md:col-span-4 space-y-1">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase">Field Name</label>
                                                    <input type="text" x-model="field.name" @input="field.label = field.name" 
                                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold outline-none focus:ring-1 focus:ring-blue-500 dark:text-slate-100">
                                                </div>
                                                <div class="md:col-span-3 space-y-1">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase">Type</label>
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
                                                <div class="md:col-span-2 space-y-1">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase">Width</label>
                                                    <select x-model="field.width"
                                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold outline-none focus:ring-1 focus:ring-blue-500 dark:text-slate-100">
                                                        <option value="100%">100%</option>
                                                        <option value="50%">50%</option>
                                                        <option value="33%">33%</option>
                                                    </select>
                                                </div>
                                                <div class="md:col-span-3 space-y-1" x-show="['select', 'radio', 'checkbox'].includes(field.type)">
                                                    <label class="text-[9px] font-black text-slate-400 uppercase">Options (csv)</label>
                                                    <input type="text" x-model="field.options_str" @input="field.options = field.options_str.split(',').map(o => o.trim()).filter(o => o !== '')"
                                                        class="w-full px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold outline-none focus:ring-1 focus:ring-blue-500 dark:text-slate-100">
                                                </div>
                                            </div>
                                            <button @click="removeField(index)" class="mt-7 text-slate-300 hover:text-rose-500 transition-colors"><i class="bi bi-trash3 text-sm"></i></button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <button @click="showModal = false" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">Cancel</button>
                        <button @click="saveType" :disabled="loading" class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
                            <span x-show="!loading" x-text="modalMode === 'add' ? 'Create Type' : 'Update Type'"></span>
                            <span x-show="loading"><i class="bi bi-arrow-repeat animate-spin"></i> Processing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function typeManager(allBlocks) {
    return {
        showModal: false,
        modalMode: 'add',
        loading: false,
        formData: {
            id: '',
            name: '',
            icon: 'bi-box',
            description: '',
            block_ids: [],
            fields: []
        },
        openAddModal() {
            this.modalMode = 'add';
            this.formData = { id: '', name: '', icon: 'bi-box', description: '', block_ids: [], fields: [] };
            this.showModal = true;
        },
        openEditModal(type) {
            this.modalMode = 'edit';
            const block_ids = JSON.parse(type.block_ids || '[]').map(String);
            const fields = JSON.parse(type.schema || '[]');
            fields.forEach(f => { if (f.options) f.options_str = f.options.join(', '); });
            this.formData = { ...type, block_ids, fields };
            this.showModal = true;
        },
        addField() {
            this.formData.fields.push({ name: '', label: '', type: 'text', width: '100%', required: false, options: [], options_str: '' });
        },
        removeField(index) { this.formData.fields.splice(index, 1); },
        async saveType() {
            if (!this.formData.name) {
                Alpine.store('app').addToast('Error', 'Type name is required.', 'error');
                return;
            }
            this.loading = true;
            try {
                const response = await fetch('index.php?route=equipment_save_type', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        id: this.formData.id,
                        name: this.formData.name,
                        icon: this.formData.icon,
                        description: this.formData.description,
                        block_ids: JSON.stringify(this.formData.block_ids),
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
        async deleteType(id) {
            Alpine.store('app').confirm('Delete Type', 'Are you sure you want to delete this equipment type? Assets of this type will lose their specific schema associations.', async () => {
                try {
                    const response = await fetch(`index.php?route=equipment_delete_type&id=${id}`);
                    const result = await response.json();
                    if (result.success) {
                        Alpine.store('app').addToast('Success', result.message, 'success');
                        window.location.reload();
                    } else {
                        Alpine.store('app').addToast('Error', result.message, 'error');
                    }
                } catch (e) {
                    Alpine.store('app').addToast('Error', 'Failed to delete type.', 'error');
                }
            });
        }
    }
}
</script>
