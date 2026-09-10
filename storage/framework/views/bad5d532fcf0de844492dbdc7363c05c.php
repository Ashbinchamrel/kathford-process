<?php
    $isProcessPayment = $payment->source !== 'manual' || $payment->vendor_bill_id;
?>
<?php $__env->startSection('title', 'Edit Payment Schedule'); ?>
<?php $__env->startSection('page-title', 'Edit Payment Schedule'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-xl"><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.edit')): ?>
<form method="POST" action="<?php echo e(route('payments.update', $payment)); ?>"><?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>"> <?php echo method_field('PUT'); ?>
    <div class="mb-4 space-y-5 rounded-xl border border-gray-200 bg-white p-6">
        <?php if($isProcessPayment): ?>
            <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm"><p class="font-bold text-blue-800"><?php echo e($payment->purchaseOrder?->po_number); ?> · <?php echo e($payment->vendor?->name); ?></p><p class="mt-1 text-xs text-blue-700">Bill and activity information are retained from the completed procurement process.</p></div>
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Payment Type <span class="text-red-500">*</span></label><select name="payment_type" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="full" <?php if(old('payment_type', $payment->payment_type ?: 'full') === 'full'): echo 'selected'; endif; ?>>Full payment</option><option value="partial" <?php if(old('payment_type', $payment->payment_type) === 'partial'): echo 'selected'; endif; ?>>Partial payment</option></select></div>
        <?php else: ?>
            <input type="hidden" name="payment_type" value="full">
        <?php endif; ?>
        <div><label class="mb-1 block text-sm font-medium text-gray-700">Account Name <span class="text-red-500">*</span></label><select name="payment_account_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select account</option><?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($account->id); ?>" <?php if(old('payment_account_id', $payment->payment_account_id) === $account->id): echo 'selected'; endif; ?>><?php echo e($account->name); ?><?php echo e($account->code ? ' · '.$account->code : ''); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div><label class="mb-1 block text-sm font-medium text-gray-700">Payment Amount (Rs) <span class="text-red-500">*</span></label><input type="number" name="amount_due" value="<?php echo e(old('amount_due', $payment->amount_due)); ?>" min="0.01" step="0.01" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono"></div>
        <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-medium text-gray-700">Scheduled Month <span class="text-red-500">*</span></label><input type="month" name="schedule_month" value="<?php echo e(old('schedule_month', $payment->schedule_month?->format('Y-m') ?: $payment->scheduled_date?->format('Y-m'))); ?>" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div><div><label class="mb-1 block text-sm font-medium text-gray-700">Scheduled Week <span class="text-red-500">*</span></label><select name="schedule_week" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><?php $__currentLoopData = range(1, 5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $week): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($week); ?>" <?php if((int) old('schedule_week', $payment->schedule_week ?: 1) === $week): echo 'selected'; endif; ?>>Week <?php echo e($week); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div></div>
        <div class="rounded-lg bg-amber-50 p-3"><label class="flex items-center gap-2 text-sm font-medium text-amber-900"><input type="hidden" name="tds_applied" value="0"><input type="checkbox" name="tds_applied" value="1" <?php if(old('tds_applied', $payment->tds_applied)): echo 'checked'; endif; ?> class="rounded border-amber-300 text-amber-600"> Apply TDS deduction</label><label class="mt-2 block text-sm text-amber-900">TDS rate (%) <input type="number" name="tds_rate" value="<?php echo e(old('tds_rate', $payment->tds_rate)); ?>" min="0" max="100" step="0.01" class="ml-2 w-24 rounded border border-amber-200 bg-white px-2 py-1 text-right"></label></div>
        <div><label class="mb-1 block text-sm font-medium text-gray-700">Notes</label><textarea name="notes" rows="2" class="w-full resize-none rounded-lg border border-gray-300 px-3 py-2 text-sm"><?php echo e(old('notes', $payment->notes)); ?></textarea></div>
    </div><div class="flex flex-wrap items-center gap-2"><button class="btn-primary">Save schedule</button><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.view')): ?>
<a href="<?php echo e(route('payments.show', $payment)); ?>" class="btn-quiet">Cancel</a>
<?php endif; ?></div>
</form>
<?php endif; ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/payments/edit.blade.php ENDPATH**/ ?>