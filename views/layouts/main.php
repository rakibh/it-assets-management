<?php
use Core\Session;

/** @var string $title */
/** @var string $view */
/** @var array $data */

$username = Session::get('username', 'User');
$role = Session::get('role', 'user');
$route = $_GET['route'] ?? 'dashboard';

// User Preferences
$userTheme = Session::get('user_theme', 'light');
$toastPosition = Session::get('user_toast_position', 'bottom-right');
$desktopNotifications = Session::get('user_desktop_notifications', false);
?>
<!DOCTYPE html>
<html lang="en" class="<?php echo $userTheme === 'dark' ? 'dark' : ''; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'IT Management System'; ?></title>
    
    <!-- Fallback Helper -->
    <script>
        function scriptFallback(src) {
            var script = document.createElement('script');
            script.src = src;
            script.defer = true;
            document.head.appendChild(script);
        }
        function styleFallback(href) {
            var link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            document.head.appendChild(link);
        }
    </script>

    <!-- Tailwind CSS with Fallback -->
    <script src="https://cdn.tailwindcss.com" onerror="scriptFallback('fallback/js/tailwind.min.js')"></script>
    <script>
        window.tailwind = window.tailwind || {};
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('app', {
                toasts: [],
                confirmModal: {
                    show: false,
                    title: '',
                    message: '',
                    onConfirm: null
                },
                notifications: {
                    unread_count: 0,
                    recent: [],
                    enabled: <?php echo $desktopNotifications ? 'true' : 'false'; ?>,
                    types: <?php echo Session::get('user_notification_types', '[]') === 'all' ? '["all"]' : (Session::get('user_notification_types') ?: '[]'); ?>,
                    last_id: parseInt(localStorage.getItem('last_notif_id')) || 0
                },
                toastConfig: {
                    position: '<?php echo $toastPosition; ?>'
                },
                timezone: '<?php echo Session::get('user_timezone', 'UTC'); ?>',
                timeFormat: '<?php echo Session::get('user_time_format', '12'); ?>',
                isTypeAllowed(type) {
                    if (!this.notifications.enabled) return false;
                    const allowed = this.notifications.types || [];
                    if (allowed.includes('all')) return true;
                    return allowed.includes(type.toLowerCase());
                },
                addToast(title, message, type = 'success') {
                    const id = Date.now();
                    this.toasts.push({ id, title, message, type, show: true });
                    setTimeout(() => this.removeToast(id), 5000);
                },
                removeToast(id) {
                    const index = this.toasts.findIndex(t => t.id === id);
                    if (index !== -1) {
                        this.toasts[index].show = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t.id !== id);
                        }, 300);
                    }
                },
                confirm(title, message, callback) {
                    this.confirmModal.title = title;
                    this.confirmModal.message = message;
                    this.confirmModal.onConfirm = callback;
                    this.confirmModal.show = true;
                },
                async requestNotificationPermission() {
                    if (!("Notification" in window)) {
                        this.addToast('Not Supported', 'This browser does not support desktop notifications.', 'error');
                        return;
                    }

                    const permission = await Notification.requestPermission();
                    if (permission === "granted") {
                        this.notifications.enabled = true;
                        localStorage.setItem('native_notifications', 'true');
                        this.addToast('Enabled', 'Desktop notifications are now active.', 'success');
                        this.showNativeNotification('Notifications Enabled', 'You will now receive system alerts on your desktop.');
                    } else {
                        this.notifications.enabled = false;
                        localStorage.setItem('native_notifications', 'false');
                        this.addToast('Denied', 'Notification permission was denied.', 'error');
                    }
                },
                showNativeNotification(title, body, data = null) {
                    if (Notification.permission !== "granted") return;

                    const n = new Notification(title, {
                        body: body,
                        icon: 'views/assets/img/logo.png' // Adjust if you have a logo
                    });

                    n.onclick = () => {
                        window.focus();
                        if (data) this.goToNotification(data);
                        n.close();
                    };
                },
                async pollNotifications() {
                    try {
                        const response = await fetch('index.php?route=notifications_poll');
                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                // Trigger Native Notifications for NEW items
                                if (this.notifications.enabled && Notification.permission === 'granted') {
                                    data.recent.forEach(n => {
                                        if (parseInt(n.id) > this.notifications.last_id && this.isTypeAllowed(n.type)) {
                                            const title = n.type.replace(/_/g, ' ').split(' ').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ') + ' Alert';
                                            this.showNativeNotification(
                                                title,
                                                n.message,
                                                n
                                            );
                                        }
                                    });
                                }

                                // Update last seen ID
                                if (data.recent.length > 0) {
                                    const maxId = Math.max(...data.recent.map(n => parseInt(n.id)));
                                    if (maxId > this.notifications.last_id) {
                                        this.notifications.last_id = maxId;
                                        localStorage.setItem('last_notif_id', maxId.toString());
                                    }
                                }

                                this.notifications.unread_count = data.unread_count;
                                this.notifications.recent = data.recent;
                            }
                        }
                    } catch (error) {
                        console.error('Failed to poll notifications:', error);
                    }
                },
                async markAllAsRead() {
                    try {
                        const response = await fetch('index.php?route=notification_mark_all_read', { method: 'POST' });
                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                this.notifications.unread_count = 0;
                                this.notifications.recent.forEach(n => n.is_read = 1);
                            }
                        }
                    } catch (error) {
                        console.error('Failed to mark notifications as read:', error);
                    }
                },
                async goToNotification(n) {
                    // Mark as read first
                    if (!n.is_read) {
                        try {
                            await fetch(`index.php?route=notification_mark_read&id=${n.id}`);
                        } catch (e) {
                            console.error('Failed to mark notification as read');
                        }
                    }

                    // Determine redirect URL
                    let url = 'index.php?route=list_notifications';
                    const data = typeof n.data === 'string' ? JSON.parse(n.data || '{}') : (n.data || {});

                    if (n.type.startsWith('task') && data.task_id) {
                        url = `index.php?route=view_task&id=${data.task_id}`;
                    } else if (n.type === 'equipment' && data.equipment_id) {
                        url = `index.php?route=view_equipment&id=${data.equipment_id}`;
                    } else if (n.type === 'network' && data.network_id) {
                        url = `index.php?route=view_network&id=${data.network_id}`;
                    } else if (n.type === 'user') {
                        url = 'index.php?route=list_users';
                    } else if (n.type === 'warranty' && data.equipment_id) {
                        url = `index.php?route=view_equipment&id=${data.equipment_id}`;
                    }

                    window.location.href = url;
                },
                timeAgo(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();
                    const seconds = Math.floor((now - date) / 1000);
                    
                    if (seconds < 60) return 'just now';
                    const minutes = Math.floor(seconds / 60);
                    if (minutes < 60) return `${minutes}m ago`;
                    const hours = Math.floor(minutes / 60);
                    if (hours < 24) return `${hours}h ago`;
                    const days = Math.floor(hours / 24);
                    if (days < 7) return `${days}d ago`;
                    return date.toLocaleDateString();
                }
            });
        });
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" onerror="scriptFallback('fallback/js/alpine.min.js')"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
          onerror="styleFallback('fallback/css/fonts/stylesheet.css')">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" 
          onerror="styleFallback('fallback/css/bootstrap-icons.min.css')">
    <style>
        [x-cloak] { display: none !important; }
        .font-inter { font-family: 'Inter', sans-serif !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; height: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
    </style>
</head>
<body class="font-inter bg-slate-50 dark:bg-slate-950 transition-colors duration-300 min-h-screen flex flex-col" 
    x-data="{ 
        mobileSidebarOpen: false, 
        darkMode: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            if (this.darkMode) document.documentElement.classList.add('dark');
            else document.documentElement.classList.remove('dark');
            localStorage.setItem('darkMode', this.darkMode);
        },
        init() {
            // Start Notification Polling
            Alpine.store('app').pollNotifications();
            setInterval(() => Alpine.store('app').pollNotifications(), 30000); // Every 30 seconds
        }
    }">

    <!-- Toast Notifications -->
    <div class="fixed z-[9999] flex flex-col gap-2 w-80 transition-all duration-500"
         :class="$store.app.toastConfig.position === 'top-right' ? 'top-4 right-4' : 'bottom-4 right-4'">
        <template x-for="toast in $store.app.toasts" :key="toast.id">
            <div x-show="toast.show" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-y-full opacity-0"
                 x-transition:enter-end="translate-y-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-y-0 opacity-100"
                 x-transition:leave-end="translate-y-full opacity-0"
                 class="p-4 rounded-xl shadow-2xl border flex items-start space-x-3 bg-white dark:bg-slate-900 dark:border-slate-800"
                 :class="toast.type === 'success' ? 'border-green-100 bg-green-50/50' : 'border-red-100 bg-red-50/50'">
                <div class="flex-shrink-0 mt-0.5">
                    <i class="bi" :class="toast.type === 'success' ? 'bi-check-circle-fill text-green-500' : 'bi-exclamation-circle-fill text-red-500'"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-slate-800 dark:text-slate-100" x-text="toast.title"></p>
                    <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5" x-text="toast.message"></p>
                </div>
                <button @click="$store.app.removeToast(toast.id)" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </template>
    </div>

    <div class="flex-1 flex">
        <!-- Static Sidebar for Desktop -->
        <div class="hidden lg:flex lg:w-72 lg:flex-col lg:fixed lg:inset-y-0 z-50">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </div>

        <!-- Mobile Sidebar Backdrop -->
        <div x-show="mobileSidebarOpen" x-cloak class="fixed inset-0 z-40 bg-slate-950/80 lg:hidden"
             x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             @click="mobileSidebarOpen = false"></div>

        <!-- Mobile Sidebar -->
        <div x-show="mobileSidebarOpen" x-cloak class="fixed inset-y-0 left-0 z-50 w-72 lg:hidden flex flex-col"
            x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" 
            x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </div>

        <!-- Main Area -->
        <div class="flex flex-col flex-1 lg:pl-72 w-full min-h-screen">
            <!-- Header / Top Bar -->
            <?php include __DIR__ . '/header.php'; ?>

            <!-- Page Content -->
            <main class="flex-1 bg-slate-50 dark:bg-slate-950 p-6 lg:p-8 transition-colors duration-300">
                <div class="<?php echo ($route === 'list_equipment' || $route === 'view_equipment' || $route === 'edit_equipment' || $route === 'add_equipment') ? 'max-w-8xl' : 'max-w-7xl'; ?> mx-auto">
                    <!-- Content -->
                    <div class="w-full">
                        <?php include $view; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Global Confirmation Modal -->
    <template x-teleport="body">
        <div x-show="$store.app.confirmModal.show" x-cloak class="fixed inset-0 z-[9999] overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
                <!-- Backdrop -->
                <div x-show="$store.app.confirmModal.show" 
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 bg-slate-900/60" 
                     @click="$store.app.confirmModal.show = false" 
                     aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <!-- Modal Content -->
                <div x-show="$store.app.confirmModal.show"
                     x-transition:enter="ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="inline-block align-bottom bg-white dark:bg-slate-900 w-full max-w-md rounded-3xl shadow-2xl border border-slate-100 dark:border-slate-800 overflow-hidden transform transition-all sm:my-8 sm:align-middle">
                    <div class="p-8 text-center">
                        <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="bi bi-exclamation-triangle-fill text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100 mb-2" x-text="$store.app.confirmModal.title"></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400" x-text="$store.app.confirmModal.message"></p>
                    </div>
                    <div class="px-8 py-6 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex justify-center gap-3">
                        <button @click="$store.app.confirmModal.show = false" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">Cancel</button>
                        <button @click="if($store.app.confirmModal.onConfirm) $store.app.confirmModal.onConfirm(); $store.app.confirmModal.show = false" class="bg-red-600 hover:bg-red-700 text-white px-8 py-2.5 rounded-xl text-sm font-bold shadow-lg shadow-red-500/20 transition-all">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</body>
</html>
