<?php
/** @var array $data */
$users = $data['users'];
$currentPage = $data['currentPage'] ?? $data['current_page'] ?? 1;
$totalPages = $data['pages'] ?? 1;
$sortBy = $data['sort_by'] ?? 'created_at';
$sortDir = $data['sort_dir'] ?? 'DESC';

function sortUrl($field, $sortBy, $sortDir) {
    $dir = ($field === $sortBy && $sortDir === 'ASC') ? 'DESC' : 'ASC';
    return "index.php?route=list_users&sort_by=$field&sort_dir=$dir";
}
?>

<div class="max-w-7xl mx-auto space-y-6" x-data="userManager()">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">System Users</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Manage system access, roles, and user accounts.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative group">
                <div class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400 group-focus-within:text-blue-500 transition-colors">
                    <i class="bi bi-search text-sm"></i>
                </div>
                <input type="text" x-model="search" @input.debounce.500ms="applySearch(1)" placeholder="Search name, username, email..." 
                    class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
            </div>
            <button @click="openAddModal" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2 whitespace-nowrap">
                <i class="bi bi-person-plus-fill text-sm"></i> Add New User
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden transition-colors duration-300">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-800">
                        <th class="px-6 py-4">
                            <a href="<?php echo sortUrl('name', $sortBy, $sortDir); ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 flex items-center gap-1 transition-colors">
                                User Details
                                <?php if($sortBy === 'name') echo $sortDir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-4">
                            <a href="<?php echo sortUrl('role', $sortBy, $sortDir); ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 flex items-center gap-1 transition-colors">
                                Role
                                <?php if($sortBy === 'role') echo $sortDir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-4">
                            <a href="<?php echo sortUrl('status', $sortBy, $sortDir); ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 flex items-center gap-1 transition-colors">
                                Status
                                <?php if($sortBy === 'status') echo $sortDir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-4">
                            <a href="<?php echo sortUrl('last_login', $sortBy, $sortDir); ?>" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-blue-600 flex items-center gap-1 transition-colors">
                                Last Login
                                <?php if($sortBy === 'last_login') echo $sortDir === 'ASC' ? '<i class="bi bi-sort-up"></i>' : '<i class="bi bi-sort-down"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 dark:divide-slate-800" id="users-table-body">
                    <?php include 'partial_list.php'; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden flex items-center justify-between px-6 py-4 transition-colors">
        <p class="text-xs text-slate-500 dark:text-slate-400">
            Showing page <span class="font-bold text-slate-800 dark:text-slate-100" x-text="currentPage"></span> of <span class="font-bold text-slate-800 dark:text-slate-100" x-text="totalPages"></span> (<?php echo $data['total']; ?> total users)
        </p>
        <div class="flex space-x-1">
            <template x-for="p in Array.from({length: totalPages}, (v, i) => i + 1)" :key="p">
                <button @click="goToPage(p)" 
                   class="w-8 h-8 flex items-center justify-center rounded-lg text-xs font-bold transition-all border"
                   :class="p === currentPage ? 'bg-blue-600 text-white shadow-md border-blue-600' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 border-slate-200 dark:border-slate-700'">
                    <span x-text="p"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- User Form Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
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

            <!-- Modal Spacer -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <!-- Modal Content -->
            <div x-show="showModal" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border dark:border-slate-800">
                
                <div class="px-8 py-6 bg-slate-800 dark:bg-slate-950 text-white flex justify-between items-center">
                    <h3 class="text-lg font-bold" x-text="modalTitle()"></h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-8 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Full Name</label>
                            <input type="text" x-model="formData.name" required 
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Username</label>
                            <input type="text" x-model="formData.username" required :disabled="modalMode === 'edit'"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all disabled:opacity-50 dark:text-slate-100">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Address</label>
                            <input type="email" x-model="formData.email" required 
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Role</label>
                            <select x-model="formData.role" required 
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                                <option value="user">Standard User</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="space-y-1.5" x-show="modalMode === 'add'">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Password</label>
                            <input type="password" x-model="formData.password" :required="modalMode === 'add'"
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Status</label>
                            <select x-model="formData.status" required 
                                class="w-full px-4 py-2.5 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showModal = false" 
                            class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all">Cancel</button>
                        <button type="submit" :disabled="loading"
                            class="px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold shadow-lg shadow-blue-500/20 transition-all flex items-center gap-2">
                            <span x-show="!loading" x-text="modalMode === 'add' ? 'Create User' : 'Save Changes'"></span>
                            <span x-show="loading"><i class="bi bi-arrow-repeat animate-spin"></i> Processing...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function userManager() {
    return {
        showModal: false,
        modalMode: 'add',
        loading: false,
        search: '<?php echo $_GET['search'] ?? ''; ?>',
        currentPage: <?php echo $currentPage; ?>,
        totalPages: <?php echo $totalPages; ?>,
        formData: {
            id: '',
            name: '',
            username: '',
            email: '',
            role: 'user',
            status: 'active',
            password: ''
        },
        modalTitle() {
            return this.modalMode === 'add' ? 'Add New User' : 'Edit User Settings';
        },
        openAddModal() {
            this.modalMode = 'add';
            this.formData = { id: '', name: '', username: '', email: '', role: 'user', status: 'active', password: '' };
            this.showModal = true;
        },
        async openEditModal(user) {
            this.modalMode = 'edit';
            this.formData = { ...user, password: '' };
            this.showModal = true;
        },
        async submitForm() {
            this.loading = true;
            try {
                const url = this.modalMode === 'add' ? 'index.php?route=add_user' : 'index.php?route=edit_user';
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(this.formData)
                });
                const result = await response.json();
                if (result.success) {
                    Alpine.store('app').addToast('Success', result.message, 'success');
                    this.showModal = false;
                    this.applySearch(this.currentPage);
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'An unexpected error occurred.', 'error');
            } finally {
                this.loading = false;
            }
        },
        async deleteUser(id) {
            Alpine.store('app').confirm('Delete User', 'Are you sure you want to delete this user? This action cannot be undone.', async () => {
                try {
                    const response = await fetch(`index.php?route=delete_user&id=${id}`);
                    const result = await response.json();
                    if (result.success) {
                        Alpine.store('app').addToast('Success', result.message, 'success');
                        this.applySearch(this.currentPage);
                    } else {
                        Alpine.store('app').addToast('Error', result.message, 'error');
                    }
                } catch (e) {
                    Alpine.store('app').addToast('Error', 'Deletion failed.', 'error');
                }
            });
        },
        async applySearch(page = 1) {
            try {
                const params = new URLSearchParams(window.location.search);
                params.set('search', this.search);
                params.set('page', page);
                
                const response = await fetch('index.php?' + params.toString() + '&ajax=1');
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('users-table-body').innerHTML = result.html;
                    this.currentPage = result.currentPage;
                    this.totalPages = result.pages;
                    window.history.pushState({}, '', 'index.php?' + params.toString());
                }
            } catch (e) {
                console.error('Search failed', e);
            }
        },
        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.applySearch(page);
        }
    }
}
</script>
