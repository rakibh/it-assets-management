<?php
/** @var array $data */
$users = $data['users'];
$currentPage = $data['currentPage'] ?? $data['current_page'] ?? 1;
$totalPages = $data['pages'] ?? 1;
$sortBy = $data['sort_by'] ?? 'created_at';
$sortDir = $data['sort_dir'] ?? 'DESC';

function sortUrl($field, $currentSortBy, $currentSortDir) {
    $dir = ($field === $currentSortBy && $currentSortDir === 'ASC') ? 'DESC' : 'ASC';
    return "index.php?route=list_users&page=1&sort_by=$field&sort_dir=$dir";
}
?>

<div x-data="userManagement()">
    <!-- Header Actions -->
    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-100 dark:border-slate-800 overflow-hidden mb-6 transition-colors duration-300">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-slate-50/50 dark:bg-slate-800/50">
            <div class="flex items-center gap-4 flex-1">
                <h3 class="font-bold text-slate-800 dark:text-slate-100 whitespace-nowrap"><?php echo \Core\I18n::t('system_users'); ?></h3>
                <div class="relative flex-1 max-w-xs">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" x-model="search" @input.debounce.500ms="applySearch(1)" placeholder="Search name, username, email..." 
                        class="w-full pl-10 pr-4 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all dark:text-slate-100">
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button @click="exportUsers" class="text-slate-600 dark:text-slate-400 hover:text-blue-600 px-3 py-2 rounded-lg text-sm font-bold flex items-center transition-all">
                    <i class="bi bi-download mr-2"></i> Export
                </button>
                <button @click="openModal('add')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-bold flex items-center transition-all shadow-sm">
                    <i class="bi bi-person-plus mr-2"></i> <?php echo \Core\I18n::t('add_user'); ?>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3 font-medium">Profile</th>
                        <th class="px-6 py-3 font-medium">
                            <a href="<?php echo sortUrl('username', $sortBy, $sortDir); ?>" class="flex items-center hover:text-blue-600 transition-colors">
                                Name / Username <?php if($sortBy === 'username') echo $sortDir === 'ASC' ? '<i class="bi bi-arrow-up ml-1"></i>' : '<i class="bi bi-arrow-down ml-1"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 font-medium">
                            <a href="<?php echo sortUrl('employee_id', $sortBy, $sortDir); ?>" class="flex items-center hover:text-blue-600 transition-colors">
                                <?php echo \Core\I18n::t('employee_id'); ?> <?php if($sortBy === 'employee_id') echo $sortDir === 'ASC' ? '<i class="bi bi-arrow-up ml-1"></i>' : '<i class="bi bi-arrow-down ml-1"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 font-medium">Contact</th>
                        <th class="px-6 py-3 font-medium">
                            <a href="<?php echo sortUrl('role', $sortBy, $sortDir); ?>" class="flex items-center hover:text-blue-600 transition-colors">
                                <?php echo \Core\I18n::t('role'); ?> <?php if($sortBy === 'role') echo $sortDir === 'ASC' ? '<i class="bi bi-arrow-up ml-1"></i>' : '<i class="bi bi-arrow-down ml-1"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 font-medium">
                            <a href="<?php echo sortUrl('status', $sortBy, $sortDir); ?>" class="flex items-center hover:text-blue-600 transition-colors">
                                <?php echo \Core\I18n::t('status'); ?> <?php if($sortBy === 'status') echo $sortDir === 'ASC' ? '<i class="bi bi-arrow-up ml-1"></i>' : '<i class="bi bi-arrow-down ml-1"></i>'; ?>
                            </a>
                        </th>
                        <th class="px-6 py-3 font-medium text-right"><?php echo \Core\I18n::t('actions'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-sm" id="user-table-body">
                    <?php require __DIR__ . '/partial_list.php'; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 bg-slate-50/50 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between" id="pagination-container">
            <p class="text-xs text-slate-500 dark:text-slate-400">
                Showing page <span class="font-bold text-slate-800 dark:text-slate-100" x-text="currentPage"></span> of <span class="font-bold text-slate-800 dark:text-slate-100" x-text="totalPages"></span> (<span x-text="totalItems"></span> total users)
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
                
                <div class="bg-slate-800 dark:bg-slate-950 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="text-lg font-bold" x-text="modalTitle()"></h3>
                    <button @click="showModal = false" class="text-slate-400 hover:text-white transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>

                <!-- FORM MODAL -->
                <div x-show="modalMode === 'add' || modalMode === 'edit'">
                    <form @submit.prevent="submitUser" class="p-6 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">First Name (Optional)</label>
                                <input type="text" x-model="formData.first_name" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Last Name (Optional)</label>
                                <input type="text" x-model="formData.last_name" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1 tracking-tight">Username <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.username" required class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold bg-slate-50/50 dark:bg-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1 tracking-tight">Employee ID <span class="text-red-500">*</span></label>
                                <input type="text" x-model="formData.employee_id" required class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold bg-slate-50/50 dark:bg-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Email (Optional)</label>
                                <input type="email" x-model="formData.email" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Phone (Optional)</label>
                                <input type="text" x-model="formData.phone" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Designation (Optional)</label>
                                <input type="text" x-model="formData.designation" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1 tracking-tight">Role <span class="text-red-500">*</span></label>
                                <select x-model="formData.role" required class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-slate-50/50 dark:bg-slate-800 dark:text-slate-100 font-bold">
                                    <option value="user">Standard User</option>
                                    <option value="admin">Administrator</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1 tracking-tight" x-text="modalMode === 'add' ? 'Password *' : 'Password (leave blank to keep)'"></label>
                                <input type="password" x-model="formData.password" :required="modalMode === 'add'" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100" :class="modalMode === 'add' ? 'bg-slate-50/50 font-bold' : ''">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1 tracking-tight">Status <span class="text-red-500">*</span></label>
                                <select x-model="formData.status" required class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm bg-slate-50/50 dark:bg-slate-800 dark:text-slate-100 font-bold">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-6 border-t dark:border-slate-800">
                            <button type="button" @click="showModal = false" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100">Cancel</button>
                            <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-bold flex items-center shadow-sm disabled:opacity-50 transition-all">
                                <span x-show="!loading" x-text="modalMode === 'add' ? 'Create User' : 'Update User'"></span>
                                <span x-show="loading" class="animate-spin mr-2"><i class="bi bi-arrow-repeat"></i></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- VIEW MODAL WITH REVISION HISTORY -->
                <div x-show="modalMode === 'view'" class="p-6">
                    <div class="flex items-center space-x-6 mb-8 pb-8 border-b border-slate-100 dark:border-slate-800">
                        <div x-show="formData.profile_photo">
                            <img :src="formData.profile_photo" class="w-24 h-24 rounded-2xl object-cover border-4 border-slate-50 dark:border-slate-800 shadow-sm">
                        </div>
                        <div x-show="!formData.profile_photo" class="w-24 h-24 rounded-2xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center text-4xl font-bold border-4 border-slate-50 dark:border-slate-800 shadow-sm" x-text="formData.username ? formData.username.charAt(0).toUpperCase() : ''"></div>
                        
                        <div>
                            <h4 class="text-2xl font-bold text-slate-900 dark:text-slate-100" x-text="(formData.first_name || '') + ' ' + (formData.last_name || '')"></h4>
                            <p class="text-slate-500 dark:text-slate-400 font-medium" x-text="formData.designation || 'No Designation'"></p>
                            <div class="flex mt-2 space-x-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400" x-text="formData.role"></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase" :class="formData.status === 'active' ? 'bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400' : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400'" x-text="formData.status"></span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Employee ID</p>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="formData.employee_id"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Username</p>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="'@' + formData.username"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase mb-1">Email Address</p>
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100" x-text="formData.email || 'N/A'"></p>
                        </div>
                    </div>
                </div>

                <!-- RESET PASSWORD MODAL -->
                <div x-show="modalMode === 'reset_password'" class="p-8">
                    <div class="text-center mb-8">
                        <div class="w-16 h-16 bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                            <i class="bi bi-shield-lock text-3xl"></i>
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 text-sm leading-relaxed">
                            Resetting password for <span class="font-bold text-slate-900 dark:text-slate-100" x-text="'@' + formData.username"></span>.<br>
                            The user will be required to choose a new password upon their next login.
                        </p>
                    </div>
                    
                    <div class="max-w-xs mx-auto space-y-4">
                        <div class="relative">
                            <label class="block text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-2">New Password</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" x-model="formData.password" 
                                    class="w-full pl-4 pr-12 py-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-2 focus:ring-orange-500 outline-none text-sm font-bold dark:text-slate-100 transition-all">
                                <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                                    <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <p class="mt-2 text-[10px] text-slate-400 italic">Min 6 chars, letters, numbers & symbols.</p>
                        </div>

                        <div class="flex flex-col gap-3 pt-4">
                            <button @click="executeReset" :disabled="loading" 
                                class="w-full bg-orange-600 hover:bg-orange-700 text-white py-3 rounded-xl text-sm font-bold shadow-lg shadow-orange-600/20 transition-all active:scale-95 disabled:opacity-50 flex items-center justify-center">
                                <span x-show="!loading">Reset & Force Change</span>
                                <i x-show="loading" class="bi bi-arrow-repeat animate-spin text-lg"></i>
                            </button>
                            <button type="button" @click="showModal = false" 
                                class="w-full py-3 text-sm font-bold text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                                Keep Current Password
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function userManagement() {
    return {
        showModal: false,
        modalMode: 'add',
        loading: false,
        showPassword: false,
        revisions: [],
        search: new URLSearchParams(window.location.search).get('search') || '',
        currentPage: <?php echo $currentPage; ?>,
        totalPages: <?php echo $totalPages; ?>,
        totalItems: <?php echo $data['total']; ?>,
        formData: {
            user_id: '',
            username: '',
            employee_id: '',
            password: '',
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            designation: '',
            profile_photo: '',
            role: 'user',
            status: 'active',
            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
        },
        modalTitle() {
            switch(this.modalMode) {
                case 'add': return 'Create New User';
                case 'edit': return 'Edit User: ' + this.formData.username;
                case 'view': return 'User Details: ' + this.formData.username;
                case 'reset_password': return 'Reset Password';
                default: return 'User Management';
            }
        },
        async openModal(mode, user = null) {
            this.modalMode = mode;
            this.revisions = [];
            if (user) {
                this.formData = { ...this.formData, ...user, user_id: user.id, password: '', csrf_token: '<?php echo $_SESSION['csrf_token']; ?>' };
            } else {
                this.formData = {
                    user_id: '', username: '', employee_id: '', password: '',
                    first_name: '', last_name: '', email: '', phone: '',
                    designation: '', profile_photo: '', role: 'user', status: 'active',
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                };
            }
            this.showModal = true;
        },
        async submitUser() {
            this.loading = true;
            try {
                const response = await fetch('index.php?route=user_store', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.formData)
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
        async executeReset() {
            if (!this.formData.password) {
                Alpine.store('app').addToast('Error', 'Please enter a new password.', 'error');
                return;
            }
            this.loading = true;
            try {
                const response = await fetch('index.php?route=user_store', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        user_id: this.formData.user_id,
                        password: this.formData.password,
                        force_password_change: 1,
                        csrf_token: this.formData.csrf_token
                    })
                });
                const result = await response.json();
                if (result.success) {
                    Alpine.store('app').addToast('Success', 'Password reset successful. User will be forced to change it on next login.', 'success');
                    setTimeout(() => {
                        this.showModal = false;
                        this.applySearch(this.currentPage);
                    }, 1500);
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Failed to reset password. Check connection or security policy.', 'error');
            } finally {
                this.loading = false;
            }
        },
        deleteUser(id, name) {
            Alpine.store('app').confirm(
                'Delete User',
                'Are you sure you want to delete user ' + name + '? This action is permanent.',
                async () => {
                    this.loading = true;
                    try {
                        const response = await fetch('index.php?route=delete_user&id=' + id);
                        const result = await response.json();
                        if (result.success) {
                            Alpine.store('app').addToast('Success', 'User deleted successfully.', 'success');
                            this.applySearch(this.currentPage);
                        } else {
                            Alpine.store('app').addToast('Error', result.message, 'error');
                        }
                    } catch (e) {
                        Alpine.store('app').addToast('Error', 'Failed to delete user.', 'error');
                    } finally {
                        this.loading = false;
                    }
                }
            );
        },
        exportUsers() {
            Alpine.store('app').addToast('Export', 'Preparing CSV download...', 'success');
            window.location.href = 'index.php?route=list_users&export=csv';
        },
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            const d = date.getDate().toString().padStart(2, '0');
            const m = (date.getMonth() + 1).toString().padStart(2, '0');
            const y = date.getFullYear();
            const h = date.getHours().toString().padStart(2, '0');
            const min = date.getMinutes().toString().padStart(2, '0');
            return `${d}/${m}/${y}, ${h}:${min}`;
        },
        async applySearch(page = 1) {
            this.currentPage = page;
            const params = new URLSearchParams(window.location.search);
            if (this.search) {
                params.set('search', this.search);
            } else {
                params.delete('search');
            }
            params.set('page', page);

            try {
                const response = await fetch('index.php?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await response.text();
                const container = document.getElementById('user-table-body');
                container.innerHTML = html;

                // Re-initialize Alpine for new content
                if (window.Alpine) {
                    Alpine.process(container);
                }
                
                // Update URL without refresh
                const newUrl = window.location.pathname + '?' + params.toString();
                window.history.pushState({ path: newUrl }, '', newUrl);

                // Sync pagination state
                const meta = document.getElementById('ajax-pagination-meta');
                if (meta) {
                    this.totalPages = parseInt(meta.getAttribute('data-pages'));
                    this.currentPage = parseInt(meta.getAttribute('data-current'));
                    this.totalItems = parseInt(meta.getAttribute('data-total'));
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
