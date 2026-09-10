<?php $__env->startSection('title', 'Payment Details'); ?>
<?php $__env->startSection('page-title', 'Payment Details'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-2xl space-y-5">
    <?php
        $hasScheduleRows = ! $payment->parent_payment_id && $payment->schedules->isNotEmpty();
    ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Purchase Order</dt>
                <dd><?php if($payment->purchaseOrder): ?><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.view')): ?>
<a href="<?php echo e(route('purchase-orders.show', $payment->purchaseOrder)); ?>" class="text-teal-600 hover:underline font-mono font-bold"><?php echo e($payment->purchaseOrder->po_number); ?></a>
<?php endif; ?>
<?php elseif($payment->source === 'direct_activity_form'): ?><span class="text-gray-700">Activity Form · <?php echo e($payment->activity_reference); ?></span><?php else: ?><span class="text-gray-700">External / manual payment</span><?php endif; ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Vendor</dt>
                <dd class="text-gray-700"><?php echo e($payment->vendor?->name ?: '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Amount</dt>
                <dd class="font-mono font-bold text-gray-800 text-lg">Rs <?php echo e(number_format($payment->amount_due, 2)); ?></dd>
            </div>
            <?php if($payment->tds_applied): ?>
            <div><dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Net payable</dt><dd class="font-mono font-bold text-teal-800 text-lg">Rs <?php echo e(number_format($payment->net_amount, 2)); ?></dd></div>
            <?php endif; ?>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Status</dt>
                <dd>
                    <?php if($payment->status === 'paid'): ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-700">Paid</span>
                    <?php elseif($payment->status === 'pending_finance'): ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-700">Awaiting Finance</span>
                    <?php elseif($payment->status === 'in_authorisation'): ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-700">In Authorisation</span>
                    <?php elseif($payment->status === 'authorised'): ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-teal-100 text-teal-700">Authorised for Payment</span>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700">Scheduled</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Scheduled Period</dt>
                <dd class="text-gray-700"><?php echo e($payment->status === 'pending_finance' ? 'To be scheduled by Finance' : ($payment->schedule_month?->format('F Y').' · Week '.$payment->schedule_week)); ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Payment Type</dt>
                <dd class="text-gray-700"><?php echo e(ucfirst($payment->payment_type ?: 'full')); ?> payment</dd>
            </div>
            <?php if($payment->account_name): ?>
            <div><dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Account Name</dt><dd class="text-gray-700"><?php echo e($payment->account_name); ?></dd></div>
            <?php endif; ?>
            <?php if($payment->sub_account): ?>
            <div><dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Sub Account</dt><dd class="text-gray-700"><?php echo e($payment->sub_account); ?></dd></div>
            <?php endif; ?>
            <?php if($payment->status === 'paid'): ?>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Actual Payment Date</dt>
                <dd class="text-gray-700"><?php echo e($payment->actual_date?->format('d M Y')); ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Transaction Reference</dt>
                <dd class="font-mono text-gray-700"><?php echo e($payment->payment_reference ?? '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Paid By</dt>
                <dd class="text-gray-700"><?php echo e($payment->markedPaidBy?->name); ?></dd>
            </div>
            <?php endif; ?>
            <?php if($payment->bank_account_number): ?>
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Bank Details (at time of scheduling)</dt>
                <dd class="font-mono text-sm bg-gray-50 rounded px-3 py-2"><?php echo e($payment->bank_name ?: 'Bank'); ?> · <?php echo e($payment->bank_account_number); ?></dd>
            </div>
            <?php endif; ?>
            <?php if($payment->notes): ?>
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Notes</dt>
                <dd class="text-gray-700"><?php echo e($payment->notes); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>

    <?php if($hasScheduleRows): ?>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4"><div><h2 class="font-semibold text-gray-900">Payment schedule</h2><p class="mt-1 text-sm text-gray-500">Scheduled payments must equal the net payable amount.</p></div><p class="font-mono text-sm font-semibold text-teal-800">Scheduled Rs <?php echo e(number_format($payment->schedules->where('status', '!=', 'cancelled')->sum('net_amount'), 2)); ?></p></div>
        <table class="min-w-full text-sm"><thead class="bg-gray-50"><tr><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Scheduled period</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Account name</th><th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Payment amount</th><th class="px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th></tr></thead><tbody class="divide-y divide-gray-100"><?php $__empty_1 = true; $__currentLoopData = $payment->schedules->where('status', '!=', 'cancelled'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td class="px-5 py-3"><?php echo e($schedule->schedule_month?->format('M Y')); ?> · Week <?php echo e($schedule->schedule_week); ?></td><td class="px-5 py-3 text-gray-600"><?php echo e($schedule->paymentAccount?->name ?: $schedule->account_name); ?></td><td class="px-5 py-3 text-right font-mono font-semibold">Rs <?php echo e(number_format($schedule->net_amount, 2)); ?></td><td class="px-5 py-3 text-gray-600"><?php echo e(ucfirst(str_replace('_', ' ', $schedule->status))); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No payment periods have been scheduled yet.</td></tr><?php endif; ?></tbody></table>
    </div>
    <?php endif; ?>

    <div class="flex flex-wrap gap-3">
        <?php if(in_array($payment->status, ['pending_finance', 'scheduled'])): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.edit')): ?>
<a href="<?php echo e(route('payments.edit', $payment)); ?>" class="btn-secondary"><?php echo e($hasScheduleRows ? 'Process payment' : ($payment->source === 'imported_vendor' ? 'Schedule payment' : 'Edit')); ?></a>
<?php endif; ?>
        <?php endif; ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.mark_paid')): ?>
            <?php if($payment->status === 'authorised'): ?>
                <button onclick="document.getElementById('mark-paid-modal').classList.remove('hidden')" class="px-5 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold">Mark as Paid</button>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.view')): ?>
<a href="<?php echo e(route('payments.index')); ?>" class="text-gray-500 hover:text-gray-700 text-sm py-2">← Back</a>
<?php endif; ?>
    </div>
</div>


<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.mark_paid')): ?>
<div id="mark-paid-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-gray-900 mb-4">Mark Payment as Paid</h3>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.mark_paid')): ?>
<form method="POST" action="<?php echo e(route('payments.mark-paid', $payment)); ?>">
            <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Actual Payment Date <span class="text-red-500">*</span></label>
                    <input type="date" name="actual_date" value="<?php echo e(date('Y-m-d')); ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Reference</label>
                    <input type="text" name="transaction_ref" placeholder="Bank ref / cheque number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button type="submit" class="flex-1 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg text-sm font-semibold">Confirm Payment</button>
                <button type="button" onclick="document.getElementById('mark-paid-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm">Cancel</button>
            </div>
        </form>
<?php endif; ?>
    </div>
</div>
<?php if(request()->boolean('settle') && $payment->status === 'authorised'): ?>
<script>document.addEventListener('DOMContentLoaded', () => document.getElementById('mark-paid-modal').classList.remove('hidden'));</script>
<?php endif; ?>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/payments/show.blade.php ENDPATH**/ ?>