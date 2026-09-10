<?php $__env->startSection('title', $grn->grn_number); ?>
<?php $__env->startSection('page-title', 'GRN: ' . $grn->grn_number); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl space-y-5">
    
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-wrap gap-6 justify-between">
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Purchase Order</p>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.view')): ?>
<a href="<?php echo e(route('purchase-orders.show', $grn->purchaseOrder)); ?>" class="text-teal-600 hover:underline font-mono font-bold"><?php echo e($grn->purchaseOrder?->po_number); ?></a>
<?php endif; ?>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Vendor</p>
                <p class="font-medium text-gray-800"><?php echo e($grn->purchaseOrder?->vendor?->name); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Date Received</p>
                <p class="font-medium text-gray-800"><?php echo e($grn->received_date?->format('d M Y')); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Status</p>
                <?php if($grn->isConfirmed()): ?>
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">Confirmed</span>
                <?php else: ?>
                    <span class="px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-700">Awaiting Confirmation</span>
                <?php endif; ?>
            </div>
        </div>
        <dl class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-0.5">Received By</dt>
                <dd class="text-gray-700"><?php echo e($grn->receivedByUser?->name); ?></dd>
            </div>
            <?php if($grn->notes): ?>
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-0.5">Remarks</dt>
                <dd class="text-gray-700"><?php echo e($grn->notes); ?></dd>
            </div>
            <?php endif; ?>
            <?php if($grn->isConfirmed()): ?>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-0.5">Confirmed By</dt>
                <dd class="text-gray-700"><?php echo e($grn->confirmedBy?->name); ?> on <?php echo e($grn->confirmed_at?->format('d M Y')); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">Items Received</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Item</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Ordered</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Received</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Condition</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__currentLoopData = $grn->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="px-5 py-3 text-gray-800"><?php echo e($item->lineItem?->item_name); ?></td>
                        <td class="px-5 py-3 text-right font-mono"><?php echo e($item->ordered_quantity); ?></td>
                        <td class="px-5 py-3 text-right font-mono <?php echo e($item->isFullyReceived() ? 'text-green-600 font-bold' : 'text-orange-600'); ?>"><?php echo e($item->received_quantity); ?></td>
                        <td class="px-5 py-3 text-gray-600"><?php echo e($item->item_condition_note ?: '—'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>

    
    <div class="flex gap-3">
        <?php if(!$grn->isConfirmed() && auth()->user()->hasAnyRole(['finance','super_admin'])): ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('grn.confirm')): ?>
<form method="POST" action="<?php echo e(route('grn.confirm', $grn)); ?>" onsubmit="return confirm('Confirm this GRN?')">
            <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
            <button class="px-5 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold">Confirm GRN</button>
        </form>
<?php endif; ?>
        <?php endif; ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('grn.view')): ?>
<a href="<?php echo e(route('grn.index')); ?>" class="text-gray-500 hover:text-gray-700 text-sm py-2">← Back</a>
<?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/grn/show.blade.php ENDPATH**/ ?>