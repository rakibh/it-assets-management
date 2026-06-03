<?php
/** @var array $data */
$blocks = $data['blocks'];
?>

<div class="max-w-6xl mx-auto space-y-8" x-data="blockManager()">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Create reusable groups of fields to attach to equipment types.</p>
        </div>
        <button @click="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center">
            <i class="bi bi-plus-lg mr-2"></i> Add New Block
        </button>
    </div>

    <!-- Blocks Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Block Name</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fields Count</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800">
                <?php foreach($blocks as $b): ?>
                    <?php 
                        $fields = json_decode($b['fields_schema'], true) ?: [];
                        $count = count($fields);
                    ?>
                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
                        <td class="px-6 py-4">
                            <span class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars($b['name']); ?></span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-xs font-medium text-slate-500"><?php echo $count; ?> fields</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button @click="openModal('edit', <?php echo htmlspecialchars(json_encode($b)); ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="View/Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button @click="deleteBlock(<?php echo $b['id']; ?>, '<?php echo htmlspecialchars($b['name']); ?>')" class="p-2 text-slate-400 hover:text-red-600 transition-colors" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if(empty($blocks)): ?>
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center">
                            <p class="text-slate-400 text-sm italic">No predefined blocks created yet.</p>
                        </td>
                    </tr>
                <?php endif; ?>
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
                        <h3 class="text-lg font-bold" x-text="modalMode === 'add' ? 'Add Predefined Block' : 'Edit Block'"></h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-white"><i class="bi bi-x-lg"></i></button>
                    </div>
                    
                    <div class="p-8 space-y-8 overflow-y-auto flex-1">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Block Name</label>
                            <input type="text" x-model="formData.name" placeholder="e.g. Server Specifications" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Form Fields</h4>
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
                                            <!-- Field Label -->
                                            <div class="md:col-span-4">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Label</label>
                                                <input type="text" x-model="field.label" @input="updateFieldName(field)" placeholder="e.g. RAM Size"
                                                    class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>

                                            <!-- Field Type -->
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

                                            <!-- Required Toggle -->
                                            <div class="md:col-span-2">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Required</label>
                                                <div class="flex items-center h-10">
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input type="checkbox" x-model="field.required" class="sr-only peer">
                                                        <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                                                    </label>
                                                </div>
                                            </div>

                                            <!-- Width -->
                                            <div class="md:col-span-3">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Column Width</label>
                                                <select x-model="field.width" 
                                                    class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="100">Full Width (100%)</option>
                                                    <option value="50">Half Width (50%)</option>
                                                    <option value="33">One Third (33%)</option>
                                                </select>
                                            </div>

                                            <!-- Additional Options for Select/Radio/Checkbox -->
                                            <template x-if="field.type === 'select' || field.type === 'radio' || field.type === 'checkbox'">
                                                <div class="md:col-span-12">
                                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Options (Comma separated)</label>
                                                    <input type="text" x-model="field.options_str" @input="updateFieldOptions(field)" placeholder="e.g. 8GB, 16GB, 32GB"
                                                        class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                                </div>
                                            </template>

                                            <!-- Placeholder -->
                                            <div class="md:col-span-12">
                                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Placeholder Hint (Optional)</label>
                                                <input type="text" x-model="field.placeholder" placeholder="e.g. Enter RAM size in GB"
                                                    class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div x-show="formData.fields.length === 0" class="py-12 border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-3xl text-center">
                                <p class="text-slate-400 text-sm italic">No fields added yet. Click "Add Field" to begin.</p>
                            </div>
                        </div>
                    </div>

                    <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <div class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
                            <span x-text="formData.fields.length"></span> Fields Defined
                        </div>
                        <div class="flex gap-3">
                            <button @click="showModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700">Cancel</button>
                            <button @click="saveBlock" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 flex items-center disabled:opacity-50">
                                <i class="bi bi-arrow-repeat animate-spin mr-2" x-show="loading"></i>
                                Save Block
                            </button>
                        </div>
                    </div>
                </div>
            </template>
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
            fields: []
        },
        openModal(mode, data = null) {
            this.modalMode = mode;
            if (data) {
                let schema = [];
                try {
                    schema = typeof data.fields_schema === 'string' ? JSON.parse(data.fields_schema) : (data.fields_schema || []);
                } catch(e) { schema = []; }
                
                // Map schema to UI fields
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
                    fields: fields
                };
            } else {
                this.formData = { id: '', name: '', fields: [] };
                this.addField(); // Add one default field
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
                .replace(/\s+/g, '_')           // Replace spaces with _
                .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
                .replace(/\-\-+/g, '_')         // Replace multiple - with single _
                .replace(/^-+/, '')             // Trim - from start of text
                .replace(/-+$/, '');            // Trim - from end of text
        },
        async saveBlock() {
            if (!this.formData.name.trim()) {
                Alpine.store('app').addToast('Validation', 'Block name is required.', 'error');
                return;
            }

            if (this.formData.fields.length === 0) {
                Alpine.store('app').addToast('Validation', 'At least one field is required.', 'error');
                return;
            }

            // Validate all fields have labels
            if (this.formData.fields.some(f => !f.label.trim())) {
                Alpine.store('app').addToast('Validation', 'All fields must have a label.', 'error');
                return;
            }

            this.loading = true;

            // Prepare schema for backend
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
                const response = await fetch('index.php?route=equipment_block_save', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.formData.id,
                        name: this.formData.name,
                        fields_schema: JSON.stringify(schema),
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
                Alpine.store('app').addToast('Error', 'Failed to save block.', 'error');
                this.loading = false;
            }
        },
        async deleteBlock(id, name) {
            Alpine.store('app').confirm('Delete Block', `Are you sure you want to delete ${name}? This will NOT delete data from equipment, but the fields will no longer appear in forms.`, async () => {
                try {
                    const response = await fetch(`index.php?route=equipment_block_delete&id=${id}`);
                    const result = await response.json();
                    if (result.success) {
                        Alpine.store('app').addToast('Deleted', result.message, 'success');
                        setTimeout(() => window.location.reload(), 1000);
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