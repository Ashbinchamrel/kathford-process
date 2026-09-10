<?php $__env->startSection('title', 'Vendors'); ?>
<?php $__env->startSection('page-title', 'Vendor Registry'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-[1600px] space-y-3">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendors.create')): ?><div class="flex justify-end gap-3"><a href="<?php echo e(route('vendors.create')); ?>" class="btn-primary">+ Add vendor</a></div><?php endif; ?>
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-3 flex-1 flex-wrap items-end">
            <label class="block flex-1 min-w-[200px]"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find a vendor</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Vendor name or contact" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase text-gray-500">Category</span><select name="category" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="">All Categories</option>
                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($cat); ?>" <?php echo e(request('category') === $cat ? 'selected' : ''); ?>><?php echo e($cat); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select></label>
            <button class="btn-primary h-[42px]">Apply filters</button>
        </form>
    </div></section>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Category</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Contact</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Mobile</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50 <?php echo e($vendor->trashed() ? 'opacity-50' : ''); ?>">
                        <td class="px-3 py-2 font-medium text-gray-900"><?php echo e($vendor->name); ?></td>
                        <td class="px-3 py-2 text-gray-500"><?php echo e($vendor->category); ?></td>
                        <td class="px-3 py-2 text-gray-500"><?php echo e($vendor->contact_person); ?></td>
                        <td class="px-3 py-2 text-gray-500"><?php echo e($vendor->mobile_number); ?></td>
                        <td class="px-3 py-2">
                            <?php if($vendor->trashed()): ?>
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs">Deleted</span>
                            <?php elseif($vendor->is_active): ?>
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">Active</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-right">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendors.view')): ?>
<a href="<?php echo e(route('vendors.show', $vendor)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View →</a>
<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No vendors found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($vendors->hasPages()): ?>
            <div class="px-3 py-2 border-t border-gray-100"><?php echo e($vendors->withQueryString()->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/vendors/index.blade.php ENDPATH**/ ?>