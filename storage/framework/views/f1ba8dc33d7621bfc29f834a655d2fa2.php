<?php $__env->startSection('title', 'User Management'); ?>
<?php $__env->startSection('page-title', 'User Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-4">
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-2 flex-1">
            <input type="text" name="search" value="<?php echo e(request('search')); ?>" placeholder="Search users…"
                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            <select name="role_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="">All Roles</option>
                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($role->id); ?>" <?php echo e(request('role_id') == $role->id ? 'selected' : ''); ?>><?php echo e($role->display_name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
            <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Filter</button>
        </form>
        <a href="<?php echo e(route('admin.users.create')); ?>" class="bg-navy-500 hover:bg-navy-600 text-white px-5 py-2 rounded-lg text-sm font-medium flex items-center gap-2 whitespace-nowrap" style="background-color:#0B1E3D;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            Add User
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Password</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Email</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Department</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">2FA</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <?php if($user->avatar): ?>
                                    <img src="<?php echo e($user->avatar); ?>" class="w-7 h-7 rounded-full" alt="">
                                <?php else: ?>
                                    <div class="w-7 h-7 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold"><?php echo e(substr($user->name,0,1)); ?></div>
                                <?php endif; ?>
                                <span class="font-medium text-gray-900"><?php echo e($user->name); ?></span>
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <?php if($user->password): ?>
                                <span class="text-green-600 text-xs font-medium">Set</span>
                            <?php else: ?>
                                <span class="text-amber-600 text-xs font-medium">Needs setup</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-gray-500"><?php echo e($user->email); ?></td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 bg-navy-50 text-navy-700 rounded text-xs font-medium" style="background-color:#EBF0F7;color:#0B1E3D;"><?php echo e($user->role?->display_name); ?></span>
                        </td>
                        <td class="px-5 py-3 text-gray-500"><?php echo e($user->department?->name ?? '—'); ?></td>
                        <td class="px-5 py-3">
                            <?php if($user->hasTwoFactorEnabled()): ?>
                                <span class="text-green-600 text-xs font-medium flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    Active
                                </span>
                            <?php else: ?>
                                <span class="text-red-500 text-xs">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo e($user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                                <?php echo e($user->is_active ? 'Active' : 'Inactive'); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-3">
                            <a href="<?php echo e(route('admin.users.edit', $user)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Edit</a>
                            <?php if(!$user->isSuperAdmin()): ?>
                            <a href="<?php echo e(route('admin.permissions.edit', $user)); ?>" class="text-indigo-600 hover:text-indigo-700 text-sm font-medium">Permissions</a>
                            <?php endif; ?>
                            <?php if($user->hasTwoFactorEnabled() && $user->id !== auth()->id()): ?>
                            <form method="POST" action="<?php echo e(route('admin.users.reset-2fa', $user)); ?>" class="inline" onsubmit="return confirm('Reset 2FA for this user?')">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="text-amber-600 hover:text-amber-700 text-sm font-medium">Reset 2FA</button>
                            </form>
                            <?php endif; ?>
                            <?php if($user->id !== auth()->id()): ?>
                            <form method="POST" action="<?php echo e(route('admin.users.destroy', $user)); ?>" class="inline" onsubmit="return confirm('Remove <?php echo e(addslashes($user->name)); ?>? Their account will be deactivated and retained in the audit trail.')">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-rose-600 hover:text-rose-700 text-sm font-medium">Delete</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="px-5 py-12 text-center text-gray-400">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($users->hasPages()): ?>
            <div class="px-5 py-3 border-t border-gray-100"><?php echo e($users->withQueryString()->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/admin/users/index.blade.php ENDPATH**/ ?>