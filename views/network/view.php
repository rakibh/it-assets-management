<?php
/** @var array $data */
include_once __DIR__ . '/../layouts/audit_helper.php';
$n = $data['node'];
?>

<div class="max-w-4xl mx-auto space-y-8">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 px-2 sm:px-0">
        <div class="flex items-center gap-5 min-w-0">
            <a href="index.php?route=list_network" 
               class="flex-shrink-0 w-12 h-12 flex items-center justify-center rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-400 hover:text-blue-600 hover:border-blue-200 dark:hover:border-blue-900 transition-all shadow-sm">
                <i class="bi bi-arrow-left text-xl"></i>
            </a>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-tight truncate font-mono">
                    <?php echo htmlspecialchars($n['ip_address'] ?? ''); ?>
                </h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-[10px] font-black rounded-md uppercase tracking-wider">
                        Node #<?php echo $n['id']; ?>
                    </span>
                    <span class="text-sm text-slate-500 font-medium truncate">
                        Infrastructure &middot; <?php echo htmlspecialchars(($n['equip_mac'] ?? $n['mac_address'] ?? '') ?: 'No MAC'); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center">
            <button onclick="window.location.href='index.php?route=list_network&edit=<?php echo $n['id']; ?>'" 
                class="w-full sm:w-auto px-8 py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl text-sm font-black shadow-xl shadow-blue-500/25 transition-all flex items-center justify-center gap-3 active:scale-95">
                <i class="bi bi-pencil-square text-lg"></i>
                <span class="whitespace-nowrap uppercase tracking-wider">Edit Node</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Main Node Details (Left 2/3) -->
        <div class="md:col-span-2 space-y-8">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8">
                <div class="flex items-center gap-3 mb-6">
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-[10px] font-black uppercase tracking-widest">
                        Network Node
                    </span>
                    <?php if($n['equipment_id']): ?>
                        <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full text-[10px] font-black uppercase tracking-widest">Assigned</span>
                    <?php else: ?>
                        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 rounded-full text-[10px] font-black uppercase tracking-widest">Available</span>
                    <?php endif; ?>
                </div>

                <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-2 font-mono tracking-tight">
                    <?php echo htmlspecialchars($n['ip_address'] ?? ''); ?>
                </h1>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 py-8 border-t border-slate-50 dark:border-slate-800">
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Cable Number</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars(($n['cable_no'] ?: '—') ?? ''); ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Added On</p>
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo date('d/m/Y', strtotime($n['created_at'])); ?></p>
                    </div>
                </div>

                <div class="pt-8 border-t border-slate-50 dark:border-slate-800">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Connection Mapping</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-8">
                        <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center">
                                <i class="bi bi-patch-check mr-2"></i> Patch Panel
                            </p>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-[9px] font-bold text-slate-500 uppercase">Panel / Port</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                        <?php echo htmlspecialchars((($n['patch_panel_no'] ?: '—') . ' / ' . ($n['patch_panel_port'] ?: '—')) ?? ''); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-slate-500 uppercase">Location</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars(($n['patch_panel_location'] ?: '—') ?? ''); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-800">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center">
                                <i class="bi bi-hdd-network mr-2"></i> Network Switch
                            </p>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-[9px] font-bold text-slate-500 uppercase">Switch / Port</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                        <?php echo htmlspecialchars((($n['switch_no'] ?: '—') . ' / ' . ($n['switch_port'] ?: '—')) ?? ''); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-[9px] font-bold text-slate-500 uppercase">Location</p>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200"><?php echo htmlspecialchars(($n['switch_location'] ?: '—') ?? ''); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($n['remarks']): ?>
                    <div class="pt-8 border-t border-slate-50 dark:border-slate-800">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Remarks</p>
                        <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed italic">
                            "<?php echo nl2br(htmlspecialchars($n['remarks'] ?? '')); ?>"
                        </p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- Sidebar: Linked Equipment (Right 1/3) -->
        <div class="space-y-8">
            <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm p-8 overflow-hidden relative transition-colors">
                <h3 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em] mb-6">Attached Equipment</h3>
                
                <?php if($n['equipment_id']): ?>
                    <div class="space-y-6">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-blue-600">
                                <i class="bi bi-pc-display text-xl"></i>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5"><?php echo htmlspecialchars($n['type_name'] ?? ''); ?></p>
                                <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                    <?php echo htmlspecialchars(($n['brand'] ?? '') . ' ' . ($n['model'] ?? '')); ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="pt-6 border-t border-slate-50 dark:border-slate-800">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Serial Number</p>
                            <p class="text-xs font-mono font-bold text-slate-700 dark:text-slate-300"><?php echo htmlspecialchars($n['serial_number'] ?? ''); ?></p>
                        </div>

                        <a href="index.php?route=view_equipment&id=<?php echo $n['equipment_id']; ?>" 
                           class="flex items-center justify-center w-full py-3 bg-slate-50 dark:bg-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 text-xs font-bold text-slate-600 dark:text-slate-400 hover:text-blue-600 rounded-xl transition-all">
                            View Equipment Details <i class="bi bi-arrow-right ml-2"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="w-16 h-16 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-200">
                            <i class="bi bi-link-45deg text-3xl"></i>
                        </div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">No Equipment Linked</p>
                        <p class="text-[10px] text-slate-500 leading-relaxed px-4">This network node is currently available for assignment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
