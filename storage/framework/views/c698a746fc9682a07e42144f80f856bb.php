<?php $__env->startSection('title', 'Purchase Orders'); ?>
<?php $__env->startSection('page-title', 'Purchase Orders'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-[1600px] space-y-3">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.create')): ?><div class="flex justify-end gap-3"><a href="<?php echo e(route('purchase-orders.create')); ?>" class="btn-primary">+ New purchase order</a></div><?php endif; ?>

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-3 flex-1 flex-wrap items-end"><input type="hidden" name="status" value="<?php echo e(request('status')); ?>"><label class="block flex-1 min-w-[200px]"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find a purchase order</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="PO number" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label><button class="btn-primary h-[42px]">Apply filters</button></form>
    </div>

    
    <?php
        $tabs = [
            ''                 => 'All',
            'generated'        => 'Generated',
            'pending_verification' => 'Awaiting Verification',
            'pending_approval' => 'Awaiting Approval',
            'approved'         => 'Approved',
            'rejected'         => 'Returned / Rejected',
            'sent_to_vendor'   => 'Issued to Vendor',
            'goods_pending'    => 'Goods Pending',
            'partially_received' => 'Partially Received',
            'fully_received'   => 'Fully Received',
            'cancelled'        => 'Cancelled',
        ];
        $currentStatus = request('status', '');
    ?>
    <div class="mt-4 -mx-1 overflow-x-auto border-t border-gray-100 px-1 pt-3"><div class="flex min-w-max gap-1.5">
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.view')): ?>
<a href="<?php echo e(route('purchase-orders.index', array_merge(request()->except('status','page'), $value ? ['status'=>$value] : []))); ?>"
           class="rounded-lg px-3 py-2 text-xs font-semibold transition
               <?php echo e($currentStatus === $value ? 'bg-[#0B1E3D] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'); ?>"
           <?php if($currentStatus === $value): ?> style="background:#0B1E3D" <?php endif; ?>>
            <?php echo e($label); ?>

        </a>
<?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div></div>
    </section>

    
    <?php if(session('success')): ?>
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <?php echo e(session('success')); ?>

    </div>
    <?php endif; ?>

    
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <?php if (isset($component)) { $__componentOriginal0603e3dd4f4a876f40524d6d16d7452a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.super-admin-bulk-delete','data' => ['module' => 'purchase_orders']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('super-admin-bulk-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => 'purchase_orders']); ?>
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
                        <?php if(auth()->user()->isSuperAdmin()): ?><th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all purchase orders" data-bulk-delete-toggle="purchase_orders"></th><?php endif; ?>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">PO Number</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Linked RFQ</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="relative px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $orders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $po): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $statusColors = [
                            'generated'          => 'bg-blue-100 text-blue-700',
                            'pending_verification' => 'bg-amber-100 text-amber-700',
                            'pending_approval'   => 'bg-yellow-100 text-yellow-700',
                            'approved'           => 'bg-green-100 text-green-700',
                            'rejected'           => 'bg-red-100 text-red-700',
                            'draft'              => 'bg-gray-100 text-gray-600',
                            'sent_to_vendor'     => 'bg-indigo-100 text-indigo-700',
                            'goods_pending'      => 'bg-amber-100 text-amber-700',
                            'partially_received' => 'bg-orange-100 text-orange-700',
                            'fully_received'     => 'bg-emerald-100 text-emerald-700',
                            'completed'          => 'bg-green-100 text-green-700',
                            'cancelled'          => 'bg-red-100 text-red-700',
                        ];
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <?php if(auth()->user()->isSuperAdmin()): ?><td class="px-3 py-3"><input type="checkbox" value="<?php echo e($po->id); ?>" aria-label="Select <?php echo e($po->po_number); ?>" data-bulk-delete-record="purchase_orders"></td><?php endif; ?>
                        <td class="px-3 py-2 font-mono text-xs font-bold text-gray-700"><?php echo e($po->po_number); ?></td>
                        <td class="px-3 py-2 text-gray-800 font-medium"><?php echo e($po->vendor?->name ?? '—'); ?></td>
                        <td class="px-3 py-2 text-xs text-gray-500">
                            <?php if($po->rfqQuote?->rfq): ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.show', $po->rfqQuote->rfq)); ?>" class="text-teal-600 hover:underline font-mono">
                                    <?php echo e($po->rfqQuote->rfq->rfq_number); ?>

                                </a>
<?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400 italic">Standalone</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-right font-mono text-gray-700">
                            Rs <?php echo e(number_format($po->total_amount, 2)); ?>

                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo e($statusColors[$po->status] ?? 'bg-gray-100 text-gray-600'); ?>">
                                <?php echo e($po->statusLabel()); ?>

                            </span>
                            <?php if($po->approvalChain): ?><p class="mt-1 max-w-40 truncate text-xs text-gray-400" title="<?php echo e($po->approvalChain->name); ?>"><?php echo e($po->approvalChain->name); ?></p><?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-400"><?php echo e($po->created_at->format('d M Y')); ?></td>
                        <td class="px-3 py-2 text-right space-x-2">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.view')): ?>
<a href="<?php echo e(route('purchase-orders.show', $po)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View →</a>
<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e(auth()->user()->isSuperAdmin() ? 8 : 7); ?>" class="px-5 py-12 text-center text-gray-400 text-sm">
                            No purchase orders yet. <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.create')): ?>
<a href="<?php echo e(route('purchase-orders.create')); ?>" class="text-teal-600 hover:underline">Create one now</a>
<?php endif; ?>.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($orders->hasPages()): ?>
        <div class="px-3 py-2 border-t border-gray-100">
            <?php echo e($orders->withQueryString()->links()); ?>

        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/purchase-orders/index.blade.php ENDPATH**/ ?>