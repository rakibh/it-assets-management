<?php
/** @var array $data */
$user = $data['user'];
$isEdit = $data['isEdit'];
?>

<div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden" x-data="userForm()">
    <div class="p-8 border-b border-slate-50">
        <h3 class="text-xl font-bold text-slate-800"><?php echo $isEdit ? 'Edit User: ' . htmlspecialchars($user['username']) : 'Create New User'; ?></h3>
        <p class="text-sm text-slate-500 mt-1">Configure account access and permissions.</p>
    </div>
    <div class="p-8">
        <form @submit.prevent="submitUser">
            <input type="hidden" name="user_id" value="<?php echo $user['id'] ?? ''; ?>">
            
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Username</label>
                        <input type="text" x-model="formData.username" required
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>

                    <!-- Employee ID -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Employee ID</label>
                        <input type="text" x-model="formData.employee_id" required
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">
                        <?php echo $isEdit ? 'New Password (leave blank to keep current)' : 'Password'; ?>
                    </label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" x-model="formData.password" <?php echo $isEdit ? '' : 'required'; ?>
                            class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                        <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-2.5 text-slate-400">
                            <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                        </button>
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Min 6 chars, 1 letter, 1 number, 1 special char.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Role -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">System Role</label>
                        <select x-model="formData.role" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="user">Standard User</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Account Status</label>
                        <select x-model="formData.status" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-100">
                    <a href="index.php?route=list_users" class="px-6 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 transition-all">Cancel</a>
                    <button type="submit" :disabled="loading"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded-lg text-sm font-bold flex items-center transition-all shadow-sm disabled:opacity-50">
                        <span x-show="!loading"><?php echo $isEdit ? 'Update User' : 'Create User'; ?></span>
                        <span x-show="loading" class="animate-spin mr-2"><i class="fas fa-spinner"></i></span>
                        <i x-show="!loading" class="fas fa-save ml-2"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function userForm() {
    return {
        formData: {
            user_id: '<?php echo $user['id'] ?? ''; ?>',
            username: '<?php echo $user['username'] ?? ''; ?>',
            employee_id: '<?php echo $user['employee_id'] ?? ''; ?>',
            password: '',
            role: '<?php echo $user['role'] ?? 'user'; ?>',
            status: '<?php echo $user['status'] ?? 'active'; ?>',
            csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
        },
        showPassword: false,
        loading: false,
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
                    setTimeout(() => window.location.href = result.redirect, 1000);
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                    this.loading = false;
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'An error occurred.', 'error');
                this.loading = false;
            }
        }
    }
}
</script>
