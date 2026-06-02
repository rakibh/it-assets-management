<?php
/** @var array $data */
$equipments = $data['equipments'];
$statusColors = [
    'In Use' => 'bg-green-100 text-green-700',
    'Available' => 'bg-blue-100 text-blue-700',
    'Under Repair' => 'bg-orange-100 text-orange-700',
    'Retired' => 'bg-slate-100 text-slate-700',
    'Lost/Stolen' => 'bg-red-100 text-red-700'
];
?>

<!-- AJAX Meta Info -->
<tr class="hidden">
    <td colspan="100">
        <div id="equipment-pagination-meta" 
             data-pages="<?php echo (int)$data['pages']; ?>" 
             data-current="<?php echo (int)$data['currentPage']; ?>"
             data-total="<?php echo (int)$data['total']; ?>">
        </div>
    </td>
</tr>

<?php foreach($equipments as $e): ?>
    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors group">
        <td class="px-6 py-4">
            <input type="checkbox" :value="<?php echo $e['id']; ?>" x-model="selectedIds" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
        </td>
        <template x-for="col in visibleColumns" :key="col.id">
            <td class="px-6 py-4">
                <template x-if="col.id === 'type'">
                    <span class="text-xs font-bold text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($e['type_name']); ?></span>
                </template>
                <template x-if="col.id === 'name'">
                    <a href="index.php?route=view_equipment&id=<?php echo $e['id']; ?>" class="text-xs font-bold text-slate-900 dark:text-white hover:text-blue-600 transition-colors">
                        <?php echo htmlspecialchars($e['name'] ?? 'N/A'); ?>
                    </a>
                </template>
                <template x-if="col.id === 'brand_model'">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars(($e['brand'] ?? '') . ' ' . ($e['model'] ?? '')); ?></span>
                        <span class="text-[10px] text-slate-400 font-medium"><?php echo htmlspecialchars($e['serial_number'] ?? ''); ?></span>
                    </div>
                </template>
                <template x-if="col.id === 'status'">
                    <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider <?php echo $statusColors[$e['status']] ?? 'bg-slate-100'; ?>">
                        <?php echo $e['status']; ?>
                    </span>
                </template>
                <template x-if="col.id === 'office_location'">
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($e['office_location'] ?? 'N/A'); ?></span>
                </template>
                <template x-if="col.id === 'floor'">
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($e['floor'] ?? 'N/A'); ?></span>
                </template>
                <template x-if="col.id === 'department'">
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($e['department'] ?? 'N/A'); ?></span>
                </template>
                <template x-if="col.id === 'assigned_to'">
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($e['assigned_to'] ?? 'N/A'); ?></span>
                </template>
                <template x-if="col.id === 'condition'">
                    <?php 
                        $conditionBadgeColors = [
                            'excellent' => 'bg-green-100 text-green-700',
                            'good' => 'bg-blue-100 text-blue-700',
                            'fair' => 'bg-yellow-100 text-yellow-700',
                            'poor' => 'bg-orange-100 text-orange-700',
                            'broken' => 'bg-red-100 text-red-700'
                        ];
                        $dbCond = !empty($e['condition']) ? strtolower((string)$e['condition']) : 'excellent';
                        $condBadgeColor = $conditionBadgeColors[$dbCond] ?? 'bg-slate-100 text-slate-700';
                    ?>
                    <span class="px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-wider <?php echo $condBadgeColor; ?>">
                        <?php echo htmlspecialchars($dbCond); ?>
                    </span>
                </template>
                <template x-if="col.id === 'network'">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-blue-600"><?php echo htmlspecialchars($e['ip_address'] ?? 'No IP'); ?></span>
                        <span class="text-[9px] text-slate-400"><?php echo htmlspecialchars($e['mac_address'] ?? ''); ?></span>
                    </div>
                </template>
                <template x-if="col.id === 'warranty'">
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400">
                        <?php echo $e['warranty_expiry'] ? date('d/m/Y', strtotime($e['warranty_expiry'])) : 'No Warranty'; ?>
                    </span>
                </template>
                <template x-if="col.id === 'created_at'">
                    <span class="text-xs font-medium text-slate-600 dark:text-slate-400">
                        <?php echo date('d/m/Y', strtotime($e['created_at'])); ?>
                    </span>
                </template>
            </td>
        </template>
        <td class="px-6 py-4 text-right">
            <div class="flex justify-end gap-2">
                <button @click="openQrModal(<?php echo $e['id']; ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="Print QR Code">
                    <i class="bi bi-qr-code"></i>
                </button>
                <a href="index.php?route=view_equipment&id=<?php echo $e['id']; ?>" class="p-2 text-slate-400 hover:text-blue-600 transition-colors">
                    <i class="bi bi-eye"></i>
                </a>
                <a href="index.php?route=edit_equipment&id=<?php echo $e['id']; ?>" class="p-2 text-slate-400 hover:text-blue-600 transition-colors">
                    <i class="bi bi-pencil-square"></i>
                </a>
                <button @click="deleteItem(<?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['serial_number'] ?? ''); ?>')" class="p-2 text-slate-400 hover:text-red-600 transition-colors">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    </tr>
<?php endforeach; ?>

<?php if (empty($equipments)): ?>
    <tr>
        <td colspan="100" class="px-6 py-20 text-center">
            <div class="flex flex-col items-center">
                <div class="w-16 h-16 bg-slate-50 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-300 mb-4">
                    <i class="bi bi-search text-3xl"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">No equipment found</h3>
                <p class="text-xs text-slate-400 mt-1">Try adjusting your search or filters.</p>
            </div>
        </td>
    </tr>
<?php endif; ?>
