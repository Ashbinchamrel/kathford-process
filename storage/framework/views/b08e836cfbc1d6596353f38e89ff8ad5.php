<?php $__env->startSection('title', 'Goods Received Notes'); ?>
<?php $__env->startSection('page-title', 'Goods Received Notes'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-4">
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <?php if (isset($component)) { $__componentOriginal0603e3dd4f4a876f40524d6d16d7452a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.super-admin-bulk-delete','data' => ['module' => 'grns']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('super-admin-bulk-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => 'grns']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a)): ?>
<?php $attributes = $__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a; ?>
<?php unset($__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0603e3dd4f4a876f40524d6d16d7452a)): ?>
<?php $component = $__componentOriginal0603e3dd4f4a876f40524d6d16d7452a; ?>
<?php unset($__componentOriginal0603e3dd4f4a876f40524d6d16d7452a); ?>
<?php endif; ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <?php if(auth()->user()->isSuperAdmin()): ?><th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all GRNs" data-bulk-delete-toggle="grns"></th><?php endif; ?>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">GRN Number</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">PO</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Received By</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $grns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr class="hover:bg-gray-50">
                        <?php if(auth()->user()->isSuperAdmin()): ?><td class="px-3 py-3"><input type="checkbox" value="<?php echo e($grn->id); ?>" aria-label="Select <?php echo e($grn->grn_number); ?>" data-bulk-delete-record="grns"></td><?php endif; ?>
                        <td class="px-5 py-3 font-mono text-xs font-bold text-gray-700"><?php echo e($grn->grn_number); ?></td>
                        <td class="px-5 py-3">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.view')): ?>
<a href="<?php echo e(route('purchase-orders.show', $grn->purchaseOrder)); ?>" class="text-teal-600 hover:underline text-xs"><?php echo e($grn->purchaseOrder?->po_number); ?></a>
<?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-gray-700"><?php echo e($grn->purchaseOrder?->vendor?->name); ?></td>
                        <td class="px-5 py-3 text-gray-600"><?php echo e($grn->receivedByUser?->name); ?></td>
                        <td class="px-5 py-3 text-xs text-gray-400"><?php echo e($grn->received_date?->format('d M Y')); ?></td>
                        <td class="px-5 py-3">
                            <?php if($grn->isConfirmed()): ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Confirmed</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Pending Confirmation</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('grn.view')): ?>
<a href="<?php echo e(route('grn.show', $grn)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View</a>
<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e(auth()->user()->isSuperAdmin() ? 8 : 7); ?>" class="px-5 py-12 text-center text-gray-400">No GRNs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($grns->hasPages()): ?>
            <div class="px-5 py-3 border-t border-gray-100"><?php echo e($grns->withQueryString()->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/grn/index.blade.php ENDPATH**/ ?>