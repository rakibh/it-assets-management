<?php
/** @var array $data */
$equipment = $data['equipment'];
$isEdit = $data['isEdit'] ?? false;
$types = $data['types'];
$blocks = $data['blocks']; // We'll still keep this for any other blocks, but we manually implement the 5 main ones

$conditions = ['excellent', 'good', 'fair', 'poor', 'broken'];
$statuses = ['In Use', 'Available', 'Under Repair', 'Retired', 'Lost/Stolen'];

// Prepare JS data
$jsData = [
    'id' => $equipment['id'] ?? '',
    'type_id' => $equipment['type_id'] ?? '',
    'name' => $equipment['name'] ?? '',
    'brand' => $equipment['brand'] ?? '',
    'model' => $equipment['model'] ?? '',
    'serial_number' => $equipment['serial_number'] ?? '',
    'mac_address' => $equipment['mac_address'] ?? '',
    'status' => $equipment['status'] ?? 'Available',
    'condition' => $equipment['condition'] ?? 'excellent',
    
    // UI Toggles
    'include_status' => true,
    'include_location' => !empty($equipment['office_location']) || !empty($equipment['floor']) || !empty($equipment['department']) || !empty($equipment['assigned_to']),
    
    // Location
    'office_location' => $equipment['office_location'] ?? '',
    'floor' => $equipment['floor'] ?? '',
    'department' => $equipment['department'] ?? '',
    'assigned_to' => $equipment['assigned_to'] ?? '',
    
    // Warranty
    'include_warranty' => !empty($equipment['warranty_seller']) || !empty($equipment['warranty_expiry']),
    'warranty_seller' => $equipment['warranty_seller'] ?? '',
    'warranty_purchase_date' => $equipment['warranty_purchase_date'] ?? '',
    'warranty_expiry' => $equipment['warranty_expiry'] ?? '',
    'warranty_file_url' => $equipment['warranty_file'] ?? '', // For viewing existing
    
    'custom_data' => ($equipment['custom_data'] ?? null) ? (is_string($equipment['custom_data']) ? json_decode($equipment['custom_data'], true) : $equipment['custom_data']) : new \stdClass(),
    
    // Network
    'include_network' => !empty($equipment['network']),
    'network' => [
        'ip_address' => $equipment['network']['ip_address'] ?? '',
        'cable_no' => $equipment['network']['cable_no'] ?? '',
        'patch_panel_no' => $equipment['network']['patch_panel_no'] ?? '',
        'patch_panel_port' => $equipment['network']['patch_panel_port'] ?? '',
        'patch_panel_location' => $equipment['network']['patch_panel_location'] ?? '',
        'switch_no' => $equipment['network']['switch_no'] ?? '',
        'switch_port' => $equipment['network']['switch_port'] ?? '',
        'switch_location' => $equipment['network']['switch_location'] ?? '',
        'remarks' => $equipment['network']['remarks'] ?? '',
    ],
    // Photos
    'include_images' => !empty(json_decode($equipment['images'] ?? '[]', true)),
    'images' => json_decode($equipment['images'] ?? '[]', true) ?: [],
    'imagesToDelete' => []
];
?>

<div class="max-w-5xl mx-auto" x-data="equipmentForm(<?php echo htmlspecialchars(json_encode($jsData)); ?>, <?php echo htmlspecialchars(json_encode($types)); ?>, <?php echo htmlspecialchars(json_encode($blocks)); ?>)">
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-xl overflow-hidden">
        <div class="bg-slate-800 px-8 py-6 text-white flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold"><?php echo $isEdit ? 'Edit Equipment' : 'Add New Equipment'; ?></h2>
                <p class="text-xs text-slate-400 mt-1">Configure your asset with the mandatory blocks below.</p>
            </div>
            <a href="index.php?route=list_equipment" class="text-slate-400 hover:text-white"><i class="bi bi-x-lg"></i></a>
        </div>

        <form @submit.prevent="submitForm" class="p-8 space-y-12" enctype="multipart/form-data">
            <!-- 0. Equipment Type Selector -->
            <div class="bg-slate-50 dark:bg-slate-800/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Equipment Type <span class="text-red-500">*</span></label>
                <select x-model="formData.type_id" @change="onTypeChange" required 
                    class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    <option value="">Select a category...</option>
                    <?php foreach($types as $t): ?>
                        <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 1. Identification Block -->
            <div class="space-y-6">
                <div class="flex items-center gap-4">
                    <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-sm font-bold">1</div>
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Identification</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6 border border-slate-100 dark:border-slate-800 rounded-3xl">
                    <div class="md:col-span-2 lg:col-span-1">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Label / Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="formData.name" required placeholder="e.g. CEO Laptop"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Brand / Manufacturer</label>
                        <input type="text" x-model="formData.brand" placeholder="e.g. Dell, HP"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Model Number</label>
                        <input type="text" x-model="formData.model" placeholder="e.g. Latitude 5420"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Serial Number</label>
                        <input type="text" x-model="formData.serial_number" placeholder="Unique Serial No. or 'N/A'"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                    <div x-show="formData.include_network" x-transition>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">MAC Address</label>
                        <input type="text" x-model="formData.mac_address" :disabled="!formData.include_network" placeholder="XX:XX:XX:XX:XX:XX"
                            class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-mono dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                    </div>
                </div>
            </div>

            <!-- 2..N Dynamic Blocks (Predefined & Custom) -->
            <template x-for="(block, index) in dynamicBlocks" :key="block.id">
                <div class="space-y-6">
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 text-purple-600 flex items-center justify-center text-sm font-bold" x-text="index + 2"></div>
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]" x-text="block.name"></h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 p-6 border border-slate-100 dark:border-slate-800 rounded-3xl">
                        <template x-for="field in block.fields" :key="field.name">
                            <div :class="{
                                'md:col-span-12': !field.width || field.width == '100',
                                'md:col-span-6': field.width == '50',
                                'md:col-span-4': field.width == '33'
                            }">
                                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">
                                    <span x-text="field.label"></span>
                                    <span x-show="field.required" class="text-red-500">*</span>
                                </label>
                                
                                <template x-if="['text', 'number', 'date', 'email', 'url', 'tel', 'time'].includes(field.type)">
                                    <input :type="field.type" x-model="formData.custom_data[field.name]" :required="field.required" :placeholder="field.placeholder || ''"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                </template>
                                
                                <template x-if="field.type === 'textarea'">
                                    <textarea x-model="formData.custom_data[field.name]" :required="field.required" :placeholder="field.placeholder || ''" rows="3"
                                        class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all"></textarea>
                                </template>

                                <template x-if="field.type === 'select'">
                                    <select x-model="formData.custom_data[field.name]" :required="field.required" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100 outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                        <option value="" x-text="field.placeholder || 'Select...'"></option>
                                        <template x-for="opt in field.options" :key="opt">
                                            <option :value="opt" x-text="opt"></option>
                                        </template>
                                    </select>
                                </template>

                                <template x-if="field.type === 'radio'">
                                    <div class="flex flex-wrap gap-4 p-2">
                                        <template x-for="opt in field.options" :key="opt">
                                            <label class="flex items-center gap-2 cursor-pointer group">
                                                <input type="radio" :name="'custom_' + field.name + '_' + block.id" :value="opt" x-model="formData.custom_data[field.name]" :required="field.required"
                                                    class="w-4 h-4 text-blue-600 border-slate-300 focus:ring-blue-500">
                                                <span class="text-sm font-medium text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white transition-colors" x-text="opt"></span>
                                            </label>
                                        </template>
                                    </div>
                                </template>

                                <template x-if="field.type === 'checkbox'">
                                    <div class="space-y-3">
                                        <template x-if="!field.options || field.options.length === 0">
                                            <div class="flex items-center h-10">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" x-model="formData.custom_data[field.name]" :required="field.required" class="sr-only peer">
                                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-blue-600"></div>
                                                    <span class="ml-3 text-xs font-bold text-slate-500" x-text="formData.custom_data[field.name] ? 'Yes' : 'No'"></span>
                                                </label>
                                            </div>
                                        </template>
                                        <template x-if="field.options && field.options.length > 0">
                                            <div class="flex flex-wrap gap-4 p-2 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl">
                                                <template x-for="opt in field.options" :key="opt">
                                                    <label class="flex items-center gap-2 cursor-pointer group">
                                                        <input type="checkbox" :value="opt" x-model="formData.custom_data[field.name]"
                                                            class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                                        <span class="text-sm font-medium text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white transition-colors" x-text="opt"></span>
                                                    </label>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- 3. Network Block (Toggle) -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 flex items-center justify-center text-sm font-bold" x-text="dynamicBlocks.length + 2"></div>
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Network Connection</h3>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="formData.include_network" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-cyan-600"></div>
                    </label>
                </div>
                
                <div x-show="formData.include_network" x-transition class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">IP Address</label>
                            <input type="text" x-model="formData.network.ip_address" placeholder="e.g. 192.168.1.100" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-mono dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Cable No</label>
                            <input type="text" x-model="formData.network.cable_no" placeholder="Cable tag/ID" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Patch Panel No</label>
                            <input type="text" x-model="formData.network.patch_panel_no" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">PP Port</label>
                            <input type="text" x-model="formData.network.patch_panel_port" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">PP Location</label>
                            <input type="text" x-model="formData.network.patch_panel_location" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Switch No</label>
                            <input type="text" x-model="formData.network.switch_no" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Switch Port</label>
                            <input type="text" x-model="formData.network.switch_port" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Switch Location</label>
                            <input type="text" x-model="formData.network.switch_location" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Network Remarks</label>
                        <textarea x-model="formData.network.remarks" rows="2" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100"></textarea>
                    </div>
                </div>
            </div>

            <!-- 4. Warranty Block (Toggle) -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 flex items-center justify-center text-sm font-bold" x-text="dynamicBlocks.length + 3"></div>
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Warranty Info</h3>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="formData.include_warranty" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-orange-600"></div>
                    </label>
                </div>

                <div x-show="formData.include_warranty" x-transition class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 space-y-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Seller / Vendor</label>
                            <input type="text" x-model="formData.warranty_seller" placeholder="e.g. Amazon, Dell Store" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Purchase Date</label>
                            <input type="date" x-model="formData.warranty_purchase_date" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Expiry Date</label>
                            <input type="date" x-model="formData.warranty_expiry" class="w-full px-5 py-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Warranty Documents (Max 10MB, PDF/Images)</label>
                        <div class="flex items-center gap-4">
                            <input type="file" @change="handleFileUpload" accept=".pdf,image/*" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:bg-orange-50 file:text-orange-700 hover:file:bg-orange-100 transition-all">
                            <template x-if="formData.warranty_file_url">
                                <a :href="formData.warranty_file_url" target="_blank" class="p-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-400 hover:text-blue-600">
                                    <i class="bi bi-file-earmark-arrow-down"></i>
                                </a>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. Status & Condition Block -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center text-sm font-bold" x-text="dynamicBlocks.length + 4"></div>
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Status & Condition</h3>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="formData.include_status" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-green-600"></div>
                    </label>
                </div>
                <div x-show="formData.include_status" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 border border-slate-100 dark:border-slate-800 rounded-3xl">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Current Status</label>
                        <select x-model="formData.status" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100">
                            <?php foreach($statuses as $s): ?>
                                <option value="<?php echo $s; ?>"><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Physical Condition</label>
                        <select x-model="formData.condition" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm font-bold dark:text-slate-100">
                            <?php foreach($conditions as $c): ?>
                                <option value="<?php echo $c; ?>"><?php echo ucfirst($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 6. Location Block -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 flex items-center justify-center text-sm font-bold" x-text="dynamicBlocks.length + 5"></div>
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Location & Allocated</h3>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="formData.include_location" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-emerald-600"></div>
                    </label>
                </div>
                <div x-show="formData.include_location" x-transition class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-6 border border-slate-100 dark:border-slate-800 rounded-3xl">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Office Location</label>
                        <input type="text" x-model="formData.office_location" placeholder="e.g. Main HQ" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Floor</label>
                        <input type="text" x-model="formData.floor" placeholder="e.g. 4th Floor" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Department / Room</label>
                        <input type="text" x-model="formData.department" placeholder="e.g. IT Lab" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Assigned To</label>
                        <input type="text" x-model="formData.assigned_to" placeholder="Name + Designation" class="w-full px-5 py-3.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm dark:text-slate-100">
                    </div>
                </div>
            </div>

            <!-- 7. Equipment Photos (Gallery) -->
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 flex items-center justify-center text-sm font-bold" x-text="dynamicBlocks.length + 6"></div>
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Equipment Photos (Gallery)</h3>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" x-model="formData.include_images" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-slate-600 peer-checked:bg-indigo-600"></div>
                    </label>
                </div>
                
                <div x-show="formData.include_images" x-transition class="p-8 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-slate-100 dark:border-slate-800 space-y-6">
                    <!-- Gallery Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <!-- Existing Images -->
                        <template x-for="(img, idx) in images" :key="'old_'+idx">
                            <div class="relative group aspect-square rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-sm">
                                <img :src="img" class="w-full h-full object-cover">
                                <button type="button" @click="removeImage(idx)" 
                                        class="absolute top-2 right-2 w-7 h-7 bg-red-500 text-white rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg">
                                    <i class="bi bi-trash-fill text-xs"></i>
                                </button>
                            </div>
                        </template>

                        <!-- New Photos Previews -->
                        <template x-for="(photo, idx) in newPhotos" :key="'new_'+idx">
                            <div class="relative group aspect-square rounded-2xl overflow-hidden border-2 border-indigo-400 bg-white dark:bg-slate-900 shadow-md">
                                <img :src="photo.preview" class="w-full h-full object-cover">
                                <div class="absolute top-2 left-2 bg-indigo-600 text-[8px] font-black text-white uppercase px-1.5 py-0.5 rounded shadow-sm">New</div>
                                <button type="button" @click="removeNewPhoto(idx)" 
                                        class="absolute top-2 right-2 w-7 h-7 bg-red-500 text-white rounded-lg flex items-center justify-center shadow-lg">
                                    <i class="bi bi-trash-fill text-xs"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Upload Input -->
                    <div class="relative group" x-show="images.length + newPhotos.length < 3">
                        <input type="file" @change="handlePhotosChange" multiple accept="image/*" 
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div class="border-2 border-dashed border-slate-200 dark:border-slate-700 rounded-2xl p-8 text-center group-hover:border-indigo-400 transition-all bg-white/50 dark:bg-slate-900/50">
                            <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-500 rounded-xl flex items-center justify-center mx-auto mb-3">
                                <i class="bi bi-images text-xl"></i>
                            </div>
                            <p class="text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest">Add Equipment Photos</p>
                            <p class="text-[10px] text-slate-400 mt-2">Max 3 photos total &middot; Max 5MB each</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden Data for Deletions -->
            <input type="hidden" name="images_to_delete" :value="JSON.stringify(imagesToDelete)">


            <!-- Submit Actions -->
            <div class="flex items-center justify-end gap-4 pt-10 border-t border-slate-50 dark:border-slate-800">
                <a href="index.php?route=list_equipment" class="px-8 py-3 text-sm font-bold text-slate-500 hover:text-slate-800 transition-all">Cancel</a>
                <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-12 py-4 rounded-2xl text-sm font-bold shadow-xl shadow-blue-500/30 transition-all flex items-center disabled:opacity-50">
                    <i class="bi bi-arrow-repeat animate-spin mr-2" x-show="loading"></i>
                    <?php echo $isEdit ? 'Update Asset' : 'Save Asset'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function equipmentForm(initialData, types, allBlocks) {
    return {
        formData: initialData,
        types: types,
        allBlocks: allBlocks,
        loading: false,
        warrantyFile: null,
        images: initialData.images || [],
        newPhotos: [], // { file, preview }
        imagesToDelete: [],
        removeImage(idx) {
            this.imagesToDelete.push(this.images[idx]);
            this.images.splice(idx, 1);
        },
        handlePhotosChange(e) {
            const files = Array.from(e.target.files);
            const remainingSlots = 3 - (this.images.length + this.newPhotos.length);

            if (files.length > remainingSlots) {
                Alpine.store('app').addToast('Limit Reached', `You can only add ${remainingSlots} more photo(s).`, 'warning');
            }

            files.slice(0, remainingSlots).forEach(file => {
                if (file.size > 5 * 1024 * 1024) {
                    Alpine.store('app').addToast('Error', `${file.name} exceeds 5MB.`, 'error');
                    return;
                }
                if (!['image/jpeg', 'image/png', 'image/jpg'].includes(file.type)) {
                    Alpine.store('app').addToast('Error', `${file.name} is not a valid image.`, 'error');
                    return;
                }

                this.newPhotos.push({
                    file: file,
                    preview: URL.createObjectURL(file)
                });
            });

            // Clear input so same files can be re-selected if removed
            e.target.value = '';
        },
        removeNewPhoto(idx) {
            URL.revokeObjectURL(this.newPhotos[idx].preview);
            this.newPhotos.splice(idx, 1);
        },
        get selectedType() {
            return this.types.find(t => t.id == this.formData.type_id);
        },
        get dynamicBlocks() {
            if (!this.selectedType) return [];
            
            let blocks = [];

            // 1. Type-specific fields (Renamed to "Specifications" and placed first)
            try {
                const typeFields = typeof this.selectedType.form_schema === 'string' ? JSON.parse(this.selectedType.form_schema) : (this.selectedType.form_schema || []);
                if (typeFields.length > 0) {
                    blocks.push({
                        id: 'type_specific',
                        name: 'Specifications',
                        fields: typeFields,
                        type: 'custom'
                    });
                }
            } catch(e) {
                console.error("Error parsing type schema", e);
            }
            
            // 2. Blocks from linked predefined blocks
            const blockIds = this.selectedType.block_ids ? (typeof this.selectedType.block_ids === 'string' ? JSON.parse(this.selectedType.block_ids) : this.selectedType.block_ids) : [];
            blockIds.forEach(bid => {
                const block = this.allBlocks.find(b => b.id == bid);
                if (block && block.fields_schema) {
                    try {
                        const blockFields = typeof block.fields_schema === 'string' ? JSON.parse(block.fields_schema) : block.fields_schema;
                        if (blockFields.length > 0) {
                            blocks.push({
                                id: block.id,
                                name: block.name,
                                fields: blockFields,
                                type: 'predefined'
                            });
                        }
                    } catch(e) { console.error("Error parsing block schema", e); }
                }
            });
            
            return blocks;
        },
        get allCustomFields() {
            let fields = [];
            this.dynamicBlocks.forEach(b => {
                fields = fields.concat(b.fields);
            });
            return fields;
        },
        onTypeChange() {
            // Keep existing data, only initialize new keys from schema
            const newCustomData = { ...this.formData.custom_data };
            this.allCustomFields.forEach(f => {
                if (newCustomData[f.name] === undefined) {
                    if (f.type === 'checkbox' && f.options && f.options.length > 0) {
                        newCustomData[f.name] = [];
                    } else {
                        newCustomData[f.name] = '';
                    }
                }
            });
            this.formData.custom_data = newCustomData;
        },
        handleFileUpload(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 10 * 1024 * 1024) {
                    Alpine.store('app').addToast('Error', 'File size exceeds 10MB.', 'error');
                    e.target.value = '';
                    return;
                }
                this.warrantyFile = file;
            }
        },
        async submitForm() {
            // Validation: Expiry Date after Purchase Date
            if (this.formData.include_warranty && this.formData.warranty_purchase_date && this.formData.warranty_expiry) {
                if (new Date(this.formData.warranty_expiry) <= new Date(this.formData.warranty_purchase_date)) {
                    Alpine.store('app').addToast('Validation Error', 'Warranty Expiry Date must be after Purchase Date.', 'error');
                    return;
                }
            }

            this.loading = true;
            
            // Use FormData for multi-part uploads
            const form = new FormData();
            const nativeForm = document.querySelector('form');
            const data = new FormData(nativeForm);

            // Append base JSON fields from Alpine state
            form.append('id', this.formData.id);
            form.append('type_id', this.formData.type_id);
            form.append('name', this.formData.name);
            form.append('brand', this.formData.brand);
            form.append('model', this.formData.model);
            form.append('serial_number', this.formData.serial_number);
            form.append('mac_address', this.formData.mac_address);
            form.append('status', this.formData.status);
            form.append('condition', this.formData.condition);
            form.append('office_location', this.formData.office_location);
            form.append('floor', this.formData.floor);
            form.append('department', this.formData.department);
            form.append('assigned_to', this.formData.assigned_to);
            form.append('warranty_seller', this.formData.warranty_seller);
            form.append('warranty_purchase_date', this.formData.warranty_purchase_date);
            form.append('warranty_expiry', this.formData.warranty_expiry);
            form.append('include_network', this.formData.include_network);
            
            form.append('network', JSON.stringify(this.formData.network));
            form.append('custom_data', JSON.stringify(this.formData.custom_data));
            form.append('images_to_delete', JSON.stringify(this.imagesToDelete));
            
            // Append newly selected files from Alpine state
            this.newPhotos.forEach(photo => {
                form.append('equipment_photos[]', photo.file);
            });
            
            if (this.warrantyFile) {
                form.append('warranty_document', this.warrantyFile);
            }

            // CSRF
            form.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

            try {
                const response = await fetch('index.php?route=equipment_save', {
                    method: 'POST',
                    body: form
                });
                const result = await response.json();
                if (result.success) {
                    Alpine.store('app').addToast('Success', result.message, 'success');
                    setTimeout(() => window.location.href = 'index.php?route=list_equipment', 1000);
                } else {
                     Alpine.store('app').addToast('Error', result.message, 'error');
                    this.loading = false;
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Submission failed.', 'error');
                this.loading = false;
            }
        }
    }
}
</script>
