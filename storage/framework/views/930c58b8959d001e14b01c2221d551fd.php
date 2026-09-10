<?php $__env->startSection('title', 'Edit Category'); ?>
<?php $__env->startSection('page-title', 'Edit Category: ' . $formCategory->name); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl">
    <form method="POST" action="<?php echo e(route('admin.form-categories.update', $formCategory)); ?>">
        <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?php echo e(old('name', $formCategory->name)); ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code</label>
                    <input type="text" value="<?php echo e($formCategory->code); ?>" disabled class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm font-mono text-gray-500">
                    <p class="text-xs text-gray-400 mt-1">Code cannot be changed after creation.</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Approval Chain</label>
                    <select name="approval_chain_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Use Default Chain</option>
                        <?php $__currentLoopData = $chains; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chain): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($chain->id); ?>" <?php echo e(old('approval_chain_id', $formCategory->approval_chain_id) == $chain->id ? 'selected' : ''); ?>><?php echo e($chain->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="<?php echo e(old('sort_order', $formCategory->sort_order)); ?>" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="description" value="<?php echo e(old('description', $formCategory->description)); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <p class="text-sm font-medium text-gray-700 mb-2">Form Features</p>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="requires_reason" value="1" <?php echo e(old('requires_reason', $formCategory->requires_reason) ? 'checked' : ''); ?> class="rounded text-teal-500">
                            Requires Reason
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="requires_logistic_table" value="1" <?php echo e(old('requires_logistic_table', $formCategory->requires_logistic_table) ? 'checked' : ''); ?> class="rounded text-teal-500">
                            Logistics Table
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="requires_attachments" value="1" <?php echo e(old('requires_attachments', $formCategory->requires_attachments) ? 'checked' : ''); ?> class="rounded text-teal-500">
                            Attachments
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="requires_vendor_selection" value="1" <?php echo e(old('requires_vendor_selection', $formCategory->requires_vendor_selection) ? 'checked' : ''); ?> class="rounded text-teal-500">
                            Vendor Selection
                        </label>
                        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="bypasses_procurement_to_payment" value="0"><input type="checkbox" name="bypasses_procurement_to_payment" value="1" <?php echo e(old('bypasses_procurement_to_payment', $formCategory->bypasses_procurement_to_payment) ? 'checked' : ''); ?> class="rounded text-teal-500"> Bypass Procurement</label>
                        <p class="text-sm text-gray-500">When checked, fully approved activities go directly to Payment Schedule for Accounts, skipping RFQs, Purchase Orders and Procurement Checklists. Otherwise, they go to RFQ preparation.</p>
                    </div>
                </div>
                <div>
                    <label class="flex items-center gap-2 cursor-pointer text-sm">
                        <input type="checkbox" name="is_active" value="1" <?php echo e(old('is_active', $formCategory->is_active) ? 'checked' : ''); ?> class="rounded text-teal-500">
                        <span class="font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>
        </div>
        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold transition-colors">Save Changes</button>
            <a href="<?php echo e(route('admin.form-categories.index')); ?>" class="text-gray-500 hover:text-gray-700 text-sm py-2.5">Cancel</a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/admin/form-categories/edit.blade.php ENDPATH**/ ?>