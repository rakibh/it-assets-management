<?php
/** @var array $data */
$types = $data['types'];
$blocks = $data['blocks'];
?>

<div class="max-w-6xl mx-auto space-y-8" x-data="typeManager(<?php echo htmlspecialchars(json_encode($blocks)); ?>)">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Define schemas and link predefined blocks for hardware assets.</p>
        </div>
        <button @click="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center">
            <i class="bi bi-plus-lg mr-2"></i> Add New Type
        </button>
    </div>

    <!-- Types Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Type Name</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Linked Blocks</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Type-Specific Fields</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                <?php foreach($types as $t): ?>
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($t['name']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                    $linkedIds = $t['block_ids'] ? json_decode($t['block_ids'], true) : [];
                                    foreach($blocks as $b) {
                                        if(in_array($b['id'], $linkedIds)) {
                                            echo '<span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-500 text-[9px] font-bold rounded">'.htmlspecialchars($b['name']).'</span>';
                                        }
                                    }
                                ?>
                                <?php if(empty($linkedIds)): ?>
                                    <span class="text-[10px] text-slate-400 italic">No blocks</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <?php 
                                $schema = $t['form_schema'] ? json_decode($t['form_schema'], true) : [];
                                $fieldCount = is_array($schema) ? count($schema) : 0;
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?php echo $fieldCount > 0 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500'; ?>">
                                <?php echo $fieldCount; ?> Fields
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button @click="openModal('edit', <?php echo htmlspecialchars(json_encode($t)); ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="View/Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button @click="deleteType(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['name']); ?>')" class="p-2 text-slate-400 hover:text-red-600 transition-colors" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
                    
                    <div class="p-8 space-y-8 overflow-y-auto flex-1">
                        <div class="grid grid-cols-1 gap-8">
                            <div>
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Type Name</label>
                                <input type="text" x-model="formData.name" placeholder="e.g. Laptop" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                            </div>
                        </div>

                        <!-- Block Selection -->
                        <div>
                            <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-4">Attach Predefined Blocks</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <template x-for="block in availableBlocks" :key="block.id">
                                    <label class="flex items-center gap-3 p-3 border border-slate-200 dark:border-slate-700 rounded-xl cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                        :class="formData.block_ids.includes(parseInt(block.id)) ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' : ''">
                                        <input type="checkbox" :value="parseInt(block.id)" x-model="formData.block_ids" class="w-4 h-4 rounded border-slate-300 text-blue-600">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200" x-text="block.name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Type Specific Fields Builder -->
                        <div class="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Type-Specific Fields</h4>
                                    <p class="text-[10px] text-slate-500 mt-1 italic">Additional fields unique to ONLY this equipment type.</p>
                                </div>
                                <button @click="addField()" class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center">
                                    <i class="bi bi-plus-circle mr-1.5"></i> Add Field
                                </button>
                            </div>

                            <div class="space-y-4">
                                <template x-for="(field, index) in formData.fields" :key="index">
                                    <div class="p-6 bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 rounded-2xl relative group">
                                        <button @click="removeField(index)" class="absolute top-4 right-4 text-slate-300 hover:text-red-500 transition-colors">
                                            <i class="bi bi-x-circle-fill"></i>
                                        </button>

                                        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
                                            <div class="md:col-span-4">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Label</label>
                                                <input type="text" x-model="field.label" @input="updateFieldName(field)" placeholder="e.g. Battery Cycle"
                                                    class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Type</label>
                                                <select x-model="field.type" 
                                                    class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="text">Text</option>
                                                    <option value="number">Number</option>
                                                    <option value="email">Email</option>
                                                    <option value="url">URL</option>
                                                    <option value="tel">Tel (Phone)</option>
                                                    <option value="select">Select Dropdown</option>
                                                    <option value="radio">Radio Buttons</option>
                                                    <option value="checkbox">Checkbox</option>
                                                    <option value="textarea">Textarea</option>
                                                    <option value="date">Date</option>
                                                    <option value="time">Time</option>
                                                </select>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Required</label>
                                                <div class="flex items-center h-10">
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" x-model="field.required" class="sr-only peer">
                                                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="md:col-span-3">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Column Width</label>
                                                <select x-model="field.width" 
                                                    class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="100">Full Width (100%)</option>
                                                    <option value="50">Half Width (50%)</option>
                                                    <option value="33">One Third (33%)</option>
                                                </select>
                                            </div>
                                            <template x-if="field.type === 'select' || field.type === 'radio' || field.type === 'checkbox'">
                                                <div class="md:col-span-12">
                                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Options (Comma separated)</label>
                                                    <input type="text" x-model="field.options_str" @input="updateFieldOptions(field)" placeholder="Option 1, Option 2"
                                                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                                </div>
                                            </template>
                                            <div class="md:col-span-12">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Placeholder Hint (Optional)</label>
                                                <input type="text" x-model="field.placeholder" placeholder="e.g. Enter value..."
                                                    class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-end gap-3">
                        <button @click="showModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700">Cancel</button>
                        <button @click="saveType" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 flex items-center disabled:opacity-50">
                            <i class="bi bi-arrow-repeat animate-spin mr-2" x-show="loading"></i>
                            Save Type
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
function typeManager(availableBlocks) {
    return {
        showModal: false,
        modalMode: 'add',
        loading: false,
        availableBlocks: availableBlocks,
        formData: {
            id: '',
            name: '',
            fields: [],
            block_ids: []
        },
        openModal(mode, data = null) {
            this.modalMode = mode;
            if (data) {
                let schema = [];
                try {
                    schema = typeof data.form_schema === 'string' ? JSON.parse(data.form_schema) : (data.form_schema || []);
                } catch(e) { schema = []; }

                const fields = schema.map(f => ({
                    label: f.label || '',
                    name: f.name || '',
                    type: f.type || 'text',
                    required: !!f.required,
                    width: f.width || '100',
                    placeholder: f.placeholder || '',
                    options: f.options || [],
                    options_str: (f.options || []).join(', ')
                }));

                this.formData = {
                    id: data.id,
                    name: data.name,
                    fields: fields,
                    block_ids: data.block_ids ? (typeof data.block_ids === 'string' ? JSON.parse(data.block_ids) : data.block_ids) : []
                };
                this.formData.block_ids = this.formData.block_ids.map(id => parseInt(id));
            } else {
                this.formData = { id: '', name: '', fields: [], block_ids: [] };
            }
            this.showModal = true;
        },
        addField() {
            this.formData.fields.push({
                label: '',
                name: '',
                type: 'text',
                required: false,
                width: '100',
                placeholder: '',
                options: [],
                options_str: ''
            });
        },
        removeField(index) {
            this.formData.fields.splice(index, 1);
        },
        updateFieldName(field) {
            if (!field.name || field.name === this.slugify(field.label.slice(0, -1))) {
                field.name = this.slugify(field.label);
            }
        },
        updateFieldOptions(field) {
            field.options = field.options_str.split(',').map(s => s.trim()).filter(s => s !== '');
        },
        slugify(text) {
            return text.toString().toLowerCase()
                .replace(/\s+/g, '_')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '_')
                .replace(/^-+/, '')
                .replace(/-+$/, '');
        },
        async saveType() {
            if (!this.formData.name.trim()) {
                Alpine.store('app').addToast('Validation', 'Name is required.', 'error');
                return;
            }

            this.loading = true;

            const schema = this.formData.fields.map(f => ({
                label: f.label,
                name: f.name || this.slugify(f.label),
                type: f.type,
                required: f.required,
                width: f.width,
                placeholder: f.placeholder,
                options: (f.type === 'select' || f.type === 'radio' || f.type === 'checkbox') ? f.options : []
            }));

            try {
                const response = await fetch('index.php?route=equipment_type_save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.formData.id,
                        name: this.formData.name,
                        block_ids: this.formData.block_ids,
                        form_schema: JSON.stringify(schema),
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
                Alpine.store('app').addToast('Error', 'Failed to save type.', 'error');
                this.loading = false;
            }
        },
        async deleteType(id, name) {
            Alpine.store('app').confirm('Delete Type', `Are you sure you want to delete ${name}? This will NOT delete equipment data, but may cause display issues for existing assets of this type.`, async () => {
                try {
                    const response = await fetch(`index.php?route=equipment_type_delete&id=${id}`);
                    const result = await response.json();
                    if (result.success) {
                        Alpine.store('app').addToast('Deleted', result.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
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