<?php
/** @var string $route */
/** @var string $role */

$navItems = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'dashboard'],
    ['id' => 'tasks', 'label' => 'Tasks', 'icon' => 'bi-list-task', 'route' => 'list_tasks'],
    ['id' => 'equipment', 'label' => 'Equipment', 'icon' => 'bi-pc-display', 'route' => 'list_equipment', 'children' => [
        ['label' => 'Equipment List', 'route' => 'list_equipment', 'icon' => 'bi-list-ul'],
        ['label' => 'Equipment Types', 'route' => 'equipment_types', 'icon' => 'bi-gear-wide-connected', 'admin_only' => true],
        ['label' => 'Predefined Blocks', 'route' => 'equipment_blocks', 'icon' => 'bi-grid-3x3-gap', 'admin_only' => true],
    ]],
    ['id' => 'network', 'label' => 'Network', 'icon' => 'bi-diagram-3', 'route' => 'list_network'],
    ['id' => 'notifications', 'label' => 'Notifications', 'icon' => 'bi-bell', 'route' => 'list_notifications', 'badge' => '$store.app.notifications.unread_count'],
];

if ($role === 'admin') {
    $navItems[] = ['id' => 'users', 'label' => 'User Management', 'icon' => 'bi-people', 'route' => 'list_users'];
}

// Find active parent ID
$activeParentId = '';
foreach ($navItems as $item) {
    if ($route === $item['route'] || (isset($item['id']) && str_contains($route, $item['id']))) {
        $activeParentId = $item['id'];
        break;
    }
}
?>

<div class="flex h-full flex-col bg-[#0b1120] dark:bg-slate-950 border-r border-white/5 dark:border-slate-900 transition-colors duration-300">
    <!-- Header: Logo Area -->
    <div class="flex h-24 items-center justify-between px-8">
        <div class="flex items-center gap-4">
            <div class="w-11 h-11 rounded-2xl bg-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/20 ring-1 ring-white/10">
                <i class="bi bi-laptop text-white text-xl"></i>
            </div>
            <h1 class="text-white text-xl font-bold tracking-tight">IT Manager</h1>
        </div>
        <!-- Close Button for Mobile -->
        <button @click="mobileSidebarOpen = false" type="button" class="lg:hidden -mr-2 p-2 text-slate-500 hover:text-white transition-colors">
            <i class="bi bi-arrow-bar-left text-xl"></i>
        </button>
    </div>

    <!-- Navigation -->
    <div class="flex flex-1 flex-col px-4 pt-4" x-data="{ openMenu: '<?php echo $activeParentId; ?>' }">
        <div class="mb-6 px-4">
            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.3em]">Main Menu</p>
        </div>
        
        <nav class="flex-1 space-y-2">
            <?php foreach ($navItems as $item): 
                $isActive = ($route === $item['route'] || (isset($item['id']) && str_contains($route, $item['id'])));
                $visibleChildren = [];
                if (isset($item['children'])) {
                    foreach ($item['children'] as $child) {
                        if (!isset($child['admin_only']) || !$child['admin_only'] || $role === 'admin') {
                            $visibleChildren[] = $child;
                        }
                    }
                }
                $hasVisibleChildren = !empty($visibleChildren);
            ?>
                <div class="space-y-1">
                    <?php if (!$hasVisibleChildren): ?>
                        <a href="index.php?route=<?php echo $item['route']; ?>" 
                           class="flex items-center justify-between px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group <?php echo $isActive 
                                ? 'bg-slate-800/80 text-white shadow-sm ring-1 ring-white/5' 
                                : 'text-slate-400 hover:text-white hover:bg-slate-800/40'; ?>">
                            <div class="flex items-center">
                                <i class="bi <?php echo $item['icon']; ?> mr-4 text-lg <?php echo $isActive ? 'text-white' : 'text-slate-500 group-hover:text-slate-300'; ?>"></i>
                                <?php echo $item['label']; ?>
                            </div>
                            <?php if (isset($item['badge'])): ?>
                                <template x-if="<?php echo $item['badge']; ?> > 0">
                                    <span class="bg-blue-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="<?php echo $item['badge']; ?>"></span>
                                </template>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <button @click="openMenu = (openMenu === '<?php echo $item['id']; ?>' ? '' : '<?php echo $item['id']; ?>')"
                                class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group <?php echo $isActive 
                                    ? 'bg-slate-800/80 text-white shadow-sm ring-1 ring-white/5' 
                                    : 'text-slate-400 hover:text-white hover:bg-slate-800/40'; ?>">
                            <div class="flex items-center">
                                <i class="bi <?php echo $item['icon']; ?> mr-4 text-lg <?php echo $isActive ? 'text-white' : 'text-slate-500 group-hover:text-slate-300'; ?>"></i>
                                <?php echo $item['label']; ?>
                            </div>
                            <i class="bi bi-chevron-down transition-transform duration-200 text-xs" :class="openMenu === '<?php echo $item['id']; ?>' ? 'rotate-180' : ''"></i>
                        </button>
                        
                        <div x-show="openMenu === '<?php echo $item['id']; ?>'" 
                             x-cloak 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 -translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="pl-12 space-y-1 mt-1">
                            <?php foreach ($visibleChildren as $child): 
                                $isChildActive = ($route === $child['route']);
                            ?>
                                <a href="index.php?route=<?php echo $child['route']; ?>" 
                                   class="flex items-center py-2 text-sm font-medium rounded-lg transition-all duration-200 <?php echo $isChildActive 
                                        ? 'text-white' 
                                        : 'text-slate-500 hover:text-slate-300'; ?>">
                                    <?php if (isset($child['icon'])): ?>
                                        <i class="bi <?php echo $child['icon']; ?> mr-3 text-base"></i>
                                    <?php endif; ?>
                                    <?php echo $child['label']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($role === 'admin'): ?>
                <div class="pt-6 mb-2 px-4">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.3em]">System Admin</p>
                </div>
                <a href="index.php?route=admin_tools" 
                   class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 group <?php echo $route === 'admin_tools' 
                        ? 'bg-slate-800/80 text-white shadow-sm ring-1 ring-white/5' 
                        : 'text-slate-400 hover:text-white hover:bg-slate-800/40'; ?>">
                    <i class="bi bi-tools mr-4 text-lg <?php echo $route === 'admin_tools' ? 'text-white' : 'text-slate-500 group-hover:text-slate-300'; ?>"></i>
                    System Tools
                </a>
            <?php endif; ?>
        </nav>
    </div>

    <!-- Bottom Fixed Section -->
    <div class="px-4 pb-5 space-y-2 mt-auto border-t border-white/5 pt-6">
        <a href="index.php?route=preferences" 
           class="flex items-center px-4 py-2 text-sm font-medium text-slate-400 rounded-xl hover:text-white hover:bg-slate-800/40 transition-all group <?php echo $route === 'preferences' ? 'bg-slate-800 text-white' : ''; ?>">
            <i class="bi bi-sliders mr-4 text-lg text-slate-500 group-hover:text-slate-300"></i>
            Preferences
        </a>
        <a href="index.php?route=logout" 
           class="flex items-center px-4 py-2 text-sm font-medium text-red-400/80 rounded-xl hover:bg-red-500/10 hover:text-red-400 transition-all group">
            <i class="bi bi-box-arrow-right mr-4 text-lg text-red-500/60 group-hover:text-red-500"></i>
            Logout
        </a>
    </div>
</div>
