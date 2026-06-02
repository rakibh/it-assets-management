<?php
/** @var array $data */
$user = $data['user'];
?>

<div class="max-w-4xl mx-auto" x-data="profileManagement(<?php echo htmlspecialchars(json_encode($user)); ?>)" x-init="if(window.location.search.includes('force=1')) showPwModal = true">
    <!-- Forced Password Change Alert -->
    <?php if (isset($_GET['force'])): ?>
        <div class="mb-6 bg-orange-50 dark:bg-orange-900/20 border-l-4 border-orange-500 p-4 rounded-r-xl shadow-sm">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="bi bi-exclamation-triangle-fill text-orange-400 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-orange-700 dark:text-orange-400 font-bold">
                        Action Required: You must update your password before continuing.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 overflow-hidden transition-colors duration-300">
        <!-- Header -->
        <div class="h-48 bg-slate-800 dark:bg-slate-950 relative">
            <div class="absolute -bottom-16 left-8 flex items-end space-x-6">
                <div class="relative group cursor-pointer" @click="$refs.photoInput.click()">
                    <template x-if="formData.profile_photo">
                        <img :src="formData.profile_photo" class="w-32 h-32 rounded-2xl object-cover border-4 border-white dark:border-slate-900 shadow-lg">
                    </template>
                    <template x-if="!formData.profile_photo">
                        <div class="w-32 h-32 rounded-2xl bg-blue-600 flex items-center justify-center text-white text-5xl font-bold border-4 border-white dark:border-slate-900 shadow-lg" x-text="formData.username.charAt(0).toUpperCase()"></div>
                    </template>
                    <div class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-2xl text-white text-[10px] font-bold uppercase tracking-widest">
                        <i class="bi bi-camera mr-2"></i> Update
                    </div>
                    <input type="file" x-ref="photoInput" class="hidden" accept="image/*" @change="uploadPhoto">
                </div>
                <div class="pb-1">
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white" x-text="(formData.first_name || '') + ' ' + (formData.last_name || '')"></h2>
                    <p class="text-slate-500 dark:text-blue-200 font-medium" x-text="formData.designation || 'No Designation Set'"></p>
                </div>
            </div>
        </div>

        <div class="pt-20 px-8 pb-8">
            <div class="flex justify-between items-start mb-12">
                <div class="flex space-x-2">
                    <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-[10px] font-bold rounded-full uppercase tracking-wider" x-text="formData.role"></span>
                    <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-[10px] font-bold rounded-full uppercase tracking-wider" x-text="formData.status"></span>
                </div>
                <button @click="saveProfile()" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-bold flex items-center shadow-md transition-all disabled:opacity-50">
                    <i class="bi bi-save mr-2" x-show="!loading"></i>
                    <i class="bi bi-arrow-repeat animate-spin mr-2" x-show="loading"></i>
                    Save Changes
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                <!-- Personal Information -->
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">Personal Information</h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1">First Name</label>
                            <input type="text" x-model="formData.first_name" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium dark:text-slate-100">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1">Last Name</label>
                            <input type="text" x-model="formData.last_name" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium dark:text-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1">Designation</label>
                        <input type="text" x-model="formData.designation" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium dark:text-slate-100">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1">Employee ID</label>
                            <input type="text" :value="formData.employee_id" disabled class="w-full px-4 py-2 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg text-sm font-bold text-slate-500 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1">Username</label>
                            <input type="text" x-model="formData.username" class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium dark:bg-slate-800 dark:text-slate-100">
                        </div>
                    </div>
                </div>

                <!-- Contact & Security -->
                <div class="space-y-6">
                    <h3 class="text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest border-b border-slate-100 dark:border-slate-800 pb-2">Contact & Security</h3>
                    
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1">Email Address</label>
                        <input type="email" x-model="formData.email" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium dark:text-slate-100">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase mb-1">Phone Number</label>
                        <input type="text" x-model="formData.phone" class="w-full px-4 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm font-medium dark:text-slate-100">
                    </div>

                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-100 dark:border-blue-900/30 mt-8">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-blue-900 dark:text-blue-400">Security Credentials</span>
                            <button @click="showPwModal = true" class="text-xs font-bold text-blue-600 hover:text-blue-800 underline">Change Password</button>
                        </div>
                        <p class="text-xs text-blue-600/70 dark:text-blue-400/60">Protect your account with a strong password (min 6 chars, with numbers and special symbols).</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Password Modal -->
    <div x-show="showPwModal" x-cloak class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center sm:p-0">
            <!-- Backdrop -->
            <div x-show="showPwModal" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60" 
                 @click="showPwModal = false" 
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div x-show="showPwModal" 
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-2xl text-left overflow-hidden shadow-2xl transform sm:my-8 sm:align-middle sm:max-w-sm sm:w-full border dark:border-slate-800">
                <div class="bg-slate-800 dark:bg-slate-950 px-6 py-4 flex justify-between items-center text-white">
                    <h3 class="text-lg font-bold">Update Password</h3>
                    <button @click="showPwModal = false" class="text-slate-400 hover:text-white transition-colors">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <form @submit.prevent="updatePassword" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1 tracking-tight">Current Password</label>
                        <input type="password" x-model="pwData.current" required class="w-full px-4 py-2 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1 tracking-tight">New Password</label>
                        <input type="password" x-model="pwData.new" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1 tracking-tight">Confirm New Password</label>
                        <input type="password" x-model="pwData.confirm" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none text-sm dark:bg-slate-800 dark:text-slate-100">
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" @click="showPwModal = false" class="px-4 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-100 transition-all">Cancel</button>
                        <button type="submit" :disabled="loading" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-bold shadow-md transition-all disabled:opacity-50">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function profileManagement(user) {
    return {
        formData: { ...user },
        pwData: { current: '', new: '', confirm: '' },
        showPwModal: false,
        loading: false,
        async uploadPhoto(event) {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('photo', file);

            this.loading = true;
            try {
                const response = await fetch('index.php?route=upload_photo', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    this.formData.profile_photo = result.path;
                    Alpine.store('app').addToast('Success', 'Photo uploaded. Saving profile...', 'success');
                    await this.saveProfile(true, true);
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Photo upload failed.', 'error');
            } finally {
                this.loading = false;
            }
        },
        async saveProfile(silent = false, reload = false) {
            this.loading = true;
            try {
                const { id, created_at, updated_at, ...cleanData } = this.formData;
                
                const response = await fetch('index.php?route=update_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        ...cleanData,
                        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                    })
                });
                const result = await response.json();
                if (result.success) {
                    if (!silent) Alpine.store('app').addToast('Success', result.message, 'success');
                    if (reload) setTimeout(() => window.location.reload(), 1000);
                } else {
                    if (!silent) Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                if (!silent) Alpine.store('app').addToast('Error', 'Failed to update profile.', 'error');
            } finally {
                if (!reload) this.loading = false;
            }
        },
        async updatePassword() {
            if (this.pwData.new !== this.pwData.confirm) {
                Alpine.store('app').addToast('Error', 'New passwords do not match!', 'error');
                return;
            }
            this.loading = true;
            try {
                const response = await fetch('index.php?route=update_profile', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: this.pwData.current,
                        password: this.pwData.new,
                        csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                    })
                });
                const result = await response.json();
                if (result.success) {
                    this.showPwModal = false;
                    this.pwData = { current: '', new: '', confirm: '' };
                    Alpine.store('app').addToast('Success', 'Password updated successfully.', 'success');
                    if (window.location.search.includes('force=1')) {
                        setTimeout(() => window.location.href = 'index.php?route=dashboard', 1500);
                    }
                } else {
                    Alpine.store('app').addToast('Error', result.message, 'error');
                }
            } catch (e) {
                Alpine.store('app').addToast('Error', 'Failed to update password.', 'error');
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
