<?php $__env->startSection('title', 'Departments'); ?>
<?php $__env->startSection('page-title', 'Departments'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <?php if(session('success')): ?>
    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->has('error')): ?>
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800"><?php echo e($errors->first('error')); ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-100 px-5 py-4">
            <h2 class="font-semibold text-gray-800">Department register</h2>
            <p class="mt-1 text-sm text-gray-500">Every department can be edited. Departments already used by people, budgets, or activity forms are protected from deletion; set them inactive instead.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Head</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Members</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Record use</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($isProtected = $dept->users_count > 0 || $dept->budgets_count > 0 || $dept->activity_forms_count > 0); ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 font-medium text-gray-900"><?php echo e($dept->name); ?></td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-500"><?php echo e($dept->code); ?></td>
                        <td class="px-5 py-3 text-gray-600"><?php echo e($dept->head?->name ?? '—'); ?></td>
                        <td class="px-5 py-3 text-gray-500"><?php echo e($dept->users_count); ?></td>
                        <td class="px-5 py-3 text-xs text-gray-500">
                            <?php if($isProtected): ?>
                            <span class="inline-flex items-center gap-1 text-amber-700"><span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>Protected</span>
                            <?php else: ?>
                            <span class="text-gray-400">No linked records</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo e($dept->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                                <?php echo e($dept->is_active ? 'Active' : 'Inactive'); ?>

                            </span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-3">
                            <a href="<?php echo e(route('admin.departments.edit', $dept)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Edit</a>
                            <?php if(! $isProtected): ?>
                            <form method="POST" action="<?php echo e(route('admin.departments.destroy', $dept)); ?>" class="inline" onsubmit="return confirm('Delete this department? This cannot be undone.')">
                                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                            </form>
                            <?php else: ?>
                            <span class="text-xs font-medium text-gray-400" title="This department has linked records. Edit it to make it inactive when it is no longer used.">Deletion protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No departments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-5 pb-3 border-b border-gray-100">Add Department</h2>
        <form method="POST" action="<?php echo e(route('admin.departments.store')); ?>">
            <?php echo csrf_field(); ?>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?php echo e(old('name')); ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="<?php echo e(old('code')); ?>" required maxlength="10" style="text-transform:uppercase"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-teal-500 outline-none">
                    <?php $__errorArgs = ['code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department Head</label>
                    <select name="head_user_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">None</option>
                        <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($user->id); ?>" <?php echo e(old('head_user_id') == $user->id ? 'selected' : ''); ?>><?php echo e($user->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold transition-colors">Add Department</button>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/admin/departments/index.blade.php ENDPATH**/ ?>