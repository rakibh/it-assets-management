<?php
/** @var array $data */
$users = $data['users'];
$currentPage = $data['currentPage'] ?? $data['current_page'] ?? 1;
$totalPages = $data['pages'] ?? 1;
?>

<!-- AJAX Meta Info -->
<tr class="hidden">
    <td colspan="100">
        <div id="ajax-pagination-meta" 
             data-pages="<?php echo $totalPages; ?>" 
             data-current="<?php echo $currentPage; ?>"
             data-total="<?php echo $data['total']; ?>">
        </div>
    </td>
</tr>

<?php foreach ($users as $user): ?>
    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
        <td class="px-6 py-4">
            <?php if (!empty($user['profile_photo'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" class="w-10 h-10 rounded-xl object-cover border border-slate-200 dark:border-slate-700 shadow-sm">
            <?php else: ?>
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center font-bold text-xs border border-blue-50 dark:border-blue-900/50">
                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                </div>
            <?php endif; ?>
        </td>
        <td class="px-6 py-4">
            <div class="flex flex-col">
                <span class="font-bold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></span>
                <span class="text-xs text-slate-500 dark:text-slate-400">@<?php echo htmlspecialchars($user['username']); ?></span>
            </div>
        </td>
        <td class="px-6 py-4 text-slate-600 dark:text-slate-300 font-medium"><?php echo htmlspecialchars($user['employee_id']); ?></td>
        <td class="px-6 py-4">
            <div class="flex flex-col text-xs space-y-0.5">
                <span class="text-slate-700 dark:text-slate-300 font-medium"><i class="bi bi-envelope mr-1 text-slate-400"></i> <?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></span>
                <span class="text-slate-500 dark:text-slate-400"><i class="bi bi-phone mr-1 text-slate-400"></i> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></span>
            </div>
        </td>
        <td class="px-6 py-4">
            <span class="px-2 py-1 rounded text-[10px] font-bold uppercase <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'; ?>">
                <?php echo $user['role']; ?>
            </span>
        </td>
        <td class="px-6 py-4">
            <span class="flex items-center text-slate-700 dark:text-slate-300">
                <span class="w-2 h-2 rounded-full mr-2 <?php echo $user['status'] === 'active' ? 'bg-green-500' : 'bg-slate-300 dark:bg-slate-600'; ?>"></span>
                <span class="capitalize"><?php echo $user['status']; ?></span>
            </span>
        </td>
        <td class="px-6 py-4 text-right">
            <div class="flex justify-end space-x-1">
                <button @click="openModal('view', <?php echo htmlspecialchars(json_encode($user)); ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="View Details">
                    <i class="bi bi-eye"></i>
                </button>
                <button @click="openModal('edit', <?php echo htmlspecialchars(json_encode($user)); ?>)" class="p-2 text-slate-400 hover:text-blue-600 transition-colors" title="Edit User">
                    <i class="bi bi-pencil-square"></i>
                </button>
                <button @click="openModal('reset_password', <?php echo htmlspecialchars(json_encode($user)); ?>)" class="p-2 text-slate-400 hover:text-orange-600 transition-colors" title="Reset Password">
                    <i class="bi bi-key"></i>
                </button>
                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                    <button @click="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" :disabled="loading" class="p-2 text-slate-400 hover:text-red-600 transition-colors disabled:opacity-50" title="Delete User">
                        <i class="bi bi-trash" x-show="!loading"></i>
                        <i class="bi bi-arrow-repeat animate-spin" x-show="loading"></i>
                    </button>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endforeach; ?>

<?php if (empty($users)): ?>
    <tr>
        <td colspan="7" class="px-6 py-12 text-center">
            <div class="flex flex-col items-center">
                <i class="bi bi-inbox text-4xl text-slate-200 mb-2"></i>
                <p class="text-slate-400 text-sm italic">No users found matching your criteria.</p>
            </div>
        </td>
    </tr>
<?php endif; ?>
