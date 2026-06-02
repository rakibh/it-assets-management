<?php
/** @var string $username */
?>
<header class="sticky top-0 z-40 flex h-20 shrink-0 items-center gap-x-4 border-b border-slate-200 bg-white dark:bg-slate-900 dark:border-slate-800 px-6 shadow-sm sm:gap-x-6 sm:px-8 transition-colors duration-300">
    <!-- Mobile Menu Button -->
    <button @click="mobileSidebarOpen = true" type="button" class="-m-2.5 p-2.5 text-slate-700 dark:text-slate-200 lg:hidden">
        <span class="sr-only">Open sidebar</span>
        <i class="bi bi-list text-2xl"></i>
    </button>

    <!-- Separator for mobile -->
    <div class="h-6 w-px bg-slate-200 dark:bg-slate-800 lg:hidden" aria-hidden="true"></div>

    <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 items-center justify-end">
        <div class="flex items-center gap-x-4 lg:gap-x-6">
            <!-- Digital Clock -->
            <div x-data="{ 
                    time: '', 
                    updateTime() {
                        const now = new Date();
                        const options = { 
                            timeZone: $store.app.timezone, 
                            hour12: $store.app.timeFormat === '12', 
                            hour: '2-digit', 
                            minute: '2-digit', 
                            second: '2-digit' 
                        };
                        this.time = new Intl.DateTimeFormat('en-US', options).format(now);
                    }
                }" 
                x-init="updateTime(); setInterval(() => updateTime(), 1000)"
                class="hidden sm:block text-base font-mono font-bold text-lime-600 dark:text-[#DFFF00]">
                <span x-text="time"></span>
            </div>

            <!-- Theme Toggle -->
            <button type="button" @click="toggleTheme()" 
                class="-m-2.5 p-2.5 text-slate-400 hover:text-blue-600 transition-colors">
                <i class="bi" :class="darkMode ? 'bi-sun-fill text-yellow-400' : 'bi-moon-stars-fill'"></i>
            </button>

            <!-- Notifications -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="-m-2.5 p-2.5 text-slate-400 hover:text-slate-500 relative">
                    <span class="sr-only">View notifications</span>
                    <i class="bi bi-bell text-xl"></i>
                    <template x-if="$store.app.notifications.unread_count > 0">
                        <span class="absolute top-2 right-2 flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500 border border-white dark:border-slate-900"></span>
                        </span>
                    </template>
                </button>

                <!-- Dropdown -->
                <div x-show="open" @click.away="open = false" x-cloak
                    class="absolute right-0 z-50 mt-2.5 w-80 origin-top-right rounded-xl bg-white dark:bg-slate-900 py-2 shadow-2xl ring-1 ring-slate-900/5 focus:outline-none border border-slate-100 dark:border-slate-800"
                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" 
                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95">
                    <div class="px-4 py-2 border-b border-slate-50 dark:border-slate-800 flex justify-between items-center">
                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">Notifications</p>
                        <button @click="$store.app.markAllAsRead()" class="text-[10px] font-bold text-blue-600 hover:text-blue-700">Mark all as read</button>
                    </div>
                    <div class="max-h-80 overflow-y-auto custom-scrollbar">
                        <template x-for="n in $store.app.notifications.recent" :key="n.id">
                            <div @click="$store.app.goToNotification(n)"
                                 class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors border-b border-slate-50 dark:border-slate-800 last:border-0 relative group cursor-pointer"
                                 :class="!n.is_read ? 'bg-blue-50/20 dark:bg-blue-900/10' : ''">
                                <div class="flex items-start gap-3">
                                    <div class="p-2 rounded-lg" :class="{
                                        'bg-blue-100 text-blue-600': n.priority === 'low',
                                        'bg-slate-100 text-slate-600': n.priority === 'medium',
                                        'bg-orange-100 text-orange-600': n.priority === 'high',
                                        'bg-red-100 text-red-600': n.priority === 'urgent'
                                    }">
                                        <i class="bi" :class="{
                                            'bi-list-check': n.type === 'task' || n.type.includes('task'),
                                            'bi-exclamation-octagon': n.type === 'task_overdue',
                                            'bi-clock-history': n.type === 'task_approaching',
                                            'bi-pc-display': n.type === 'equipment',
                                            'bi-diagram-3': n.type === 'network',
                                            'bi-people': n.type === 'user',
                                            'bi-shield-check': n.type === 'warranty',
                                            'bi-exclamation-triangle-fill': n.priority === 'urgent',
                                            'bi-bell': !['task', 'task_overdue', 'task_approaching', 'equipment', 'network', 'user', 'warranty'].includes(n.type) && n.priority !== 'urgent'
                                        }"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-slate-800 dark:text-slate-100 line-clamp-2" x-text="n.message"></p>
                                        <div class="flex items-center justify-between mt-1">
                                            <p class="text-[10px] text-slate-400" x-text="$store.app.timeAgo(n.created_at)"></p>
                                            <template x-if="n.read_at">
                                                <p class="text-[9px] text-slate-400 italic" x-text="'Read ' + $store.app.timeAgo(n.read_at)"></p>
                                            </template>
                                        </div>
                                    </div>
                                    <div x-show="!n.is_read" class="flex-shrink-0 pt-1">
                                        <span class="block h-1.5 w-1.5 rounded-full bg-blue-600"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="$store.app.notifications.recent.length === 0">
                            <div class="px-4 py-8 text-center">
                                <p class="text-xs text-slate-400 italic">No notifications yet.</p>
                            </div>
                        </template>
                    </div>
                    <div class="px-4 py-2 text-center border-t border-slate-50 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/50 rounded-b-xl">
                        <a href="index.php?route=list_notifications" class="text-xs font-bold text-blue-600 hover:text-blue-700">View all notifications</a>
                    </div>
                </div>
            </div>

            <!-- Separator -->
            <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-slate-200 dark:bg-slate-800" aria-hidden="true"></div>

            <!-- Profile dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" type="button" class="-m-1.5 flex items-center p-1.5 group" id="user-menu-button">
                    <div class="h-9 w-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-700 dark:text-slate-200 font-bold border border-slate-200 dark:border-slate-700 group-hover:border-blue-300 transition-colors overflow-hidden">
                        <?php 
                        $profilePhoto = \Core\Session::get('profile_photo');
                        if ($profilePhoto): ?>
                            <img src="<?php echo htmlspecialchars($profilePhoto); ?>" class="h-full w-full object-cover">
                        <?php else: ?>
                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <span class="hidden lg:flex lg:items-center">
                        <span class="ml-4 text-sm font-semibold leading-6 text-slate-900 dark:text-slate-100" aria-hidden="true"><?php echo htmlspecialchars($username); ?></span>
                        <i class="bi bi-chevron-down ml-2 text-slate-400 text-xs transition-transform" :class="open ? 'rotate-180' : ''"></i>
                    </span>
                </button>

                <div x-show="open" @click.away="open = false" x-cloak
                    class="absolute right-0 z-50 mt-2.5 w-48 origin-top-right rounded-xl bg-white dark:bg-slate-900 py-2 shadow-2xl ring-1 ring-slate-900/5 focus:outline-none border border-slate-100 dark:border-slate-800" 
                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" 
                    x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95">
                    <a href="index.php?route=profile" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        <i class="bi bi-person mr-2 text-slate-400"></i> My Profile
                    </a>
                    <a href="index.php?route=preferences" class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        <i class="bi bi-sliders mr-2 text-slate-400"></i> Preferences
                    </a>
                    <div class="h-px bg-slate-100 dark:bg-slate-800 my-1 mx-2"></div>
                    <a href="index.php?route=logout" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <i class="bi bi-box-arrow-right mr-2"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
