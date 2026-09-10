<?php $__env->startSection('title', $paymentAuthorisation->authorisation_number); ?>
<?php $__env->startSection('page-title', 'Payment Authorisation'); ?>

<?php $__env->startSection('content'); ?>
<?php ($authorisation = $paymentAuthorisation); ?>
<div class="max-w-5xl space-y-5">
    <?php if($errors->any()): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
            <?php echo e($errors->first()); ?>

        </div>
    <?php endif; ?>
    <section class="flex flex-col justify-between gap-4 rounded-xl bg-[#0B1E3D] p-6 text-white sm:flex-row sm:items-start">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-teal-300">Payment authorisation</p>
            <h1 class="mt-1 font-mono text-2xl font-bold"><?php echo e($authorisation->authorisation_number); ?></h1>
            <p class="mt-2 text-sm text-slate-300"><?php echo e($authorisation->schedule_month?->format('F Y')); ?> · Week <?php echo e($authorisation->schedule_week); ?> · <?php echo e($authorisation->paymentAuthorisationChannel?->name ?: 'Legacy channel'); ?></p>
        </div>
        <div class="sm:text-right">
            <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold"><?php echo e($authorisation->statusLabel()); ?></span>
            <p class="mt-3 text-xs text-slate-300">Authorised amount</p>
            <p class="font-mono text-2xl font-bold">Rs <?php echo e(number_format($authorisation->total_amount, 2)); ?></p>
        </div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-100 px-6 py-4">
            <h2 class="font-semibold text-gray-900">Included payment schedules</h2>
            <p class="mt-1 text-sm text-gray-500">Payments grouped under this account and week.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr><th class="px-5 py-3">Payee / account</th><th class="px-5 py-3">Bill / activity</th><th class="px-5 py-3">Bank details</th><th class="px-5 py-3 text-right">Amount</th><th class="px-5 py-3">Settlement</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__currentLoopData = $authorisation->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td class="px-5 py-4"><p class="font-medium text-gray-900"><?php echo e($payment->vendor?->name ?: $payment->account_name ?: 'External payment'); ?></p><?php if($payment->sub_account): ?><p class="mt-1 text-xs text-gray-500"><?php echo e($payment->sub_account); ?></p><?php endif; ?></td>
                        <td class="px-5 py-4"><p class="text-gray-800"><?php echo e($payment->bill_number ?: 'No bill reference'); ?></p><p class="mt-1 text-xs text-gray-500"><?php echo e($payment->activity_name ?: $payment->activity_reference ?: '—'); ?></p></td>
                        <td class="px-5 py-4"><p class="text-gray-700"><?php echo e($payment->bank_name ?: '—'); ?></p><p class="mt-1 font-mono text-xs text-gray-500"><?php echo e($payment->bank_account_number ?: 'No account number'); ?></p></td>
                        <td class="px-5 py-4 text-right font-mono font-semibold text-gray-900">Rs <?php echo e(number_format($payment->net_amount ?: $payment->amount_due, 2)); ?></td>
                        <td class="px-5 py-4">
                            <?php if($payment->status === 'paid'): ?>
                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-800">Paid</span>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($payment->actual_date?->format('d M Y')); ?><?php echo e($payment->payment_reference ? ' · '.$payment->payment_reference : ''); ?></p>
                            <?php elseif($payment->status === 'authorised'): ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.mark_paid')): ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.view')): ?>
<a href="<?php echo e(route('payments.show', ['payment' => $payment, 'settle' => 1])); ?>" class="inline-flex rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Record payment</a>
<?php endif; ?>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Awaiting Accounts</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"><?php echo e(ucfirst(str_replace('_', ' ', $payment->status))); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><h2 class="font-semibold text-gray-900">Approval workflow</h2><p class="mt-1 text-sm text-gray-500"><?php echo e($authorisation->approvalChain ? 'Channel: '.($authorisation->paymentAuthorisationChannel?->name ?: 'Legacy channel').' · Chain: '.$authorisation->approvalChain->name : 'A chain is assigned when this authorisation is submitted.'); ?></p><?php if($authorisation->approvalChain && ($step = app(\App\Services\ApprovalService::class)->pendingStepLabel($authorisation))): ?><p class="mt-2 text-xs font-semibold text-amber-800">Pending: <?php echo e($step); ?></p><?php endif; ?></div>
            <?php if(auth()->user()->can('payment_authorisations.export') && $authorisation->status === 'approved'): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payment_authorisations.export')): ?>
<a href="<?php echo e(route('payment-authorisations.csv', $authorisation)); ?>" class="btn-primary">Download bank CSV</a>
<?php endif; ?>
            <?php endif; ?>
        </div>

        <?php if($authorisation->approvalActions->isNotEmpty()): ?>
        <div class="mt-5 space-y-2 border-t border-gray-100 pt-4">
            <?php $__currentLoopData = $authorisation->approvalActions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex flex-wrap items-start justify-between gap-3 rounded-lg bg-gray-50 px-4 py-3 text-sm">
                <div><p class="text-gray-800"><span class="font-semibold"><?php echo e($action->actor?->name); ?></span> · <?php echo e(ucfirst(str_replace('_', ' ', $action->decision))); ?></p><?php if($action->note): ?><p class="mt-1 text-xs text-gray-500"><?php echo e($action->note); ?></p><?php endif; ?></div>
                <p class="text-xs text-gray-400"><?php echo e($action->acted_at?->format('d M Y H:i')); ?></p>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endif; ?>

        <?php if(auth()->user()->can('payment_authorisations.submit') && $authorisation->isEditable() && (auth()->user()->isSuperAdmin() || $authorisation->created_by === auth()->id())): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payment_authorisations.submit')): ?>
<form method="POST" action="<?php echo e(route('payment-authorisations.submit', $authorisation)); ?>" class="mt-5 border-t border-gray-100 pt-5">
                <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-sm font-semibold text-gray-900">Ready to submit?</p><p class="mt-1 text-xs text-gray-500">The centrally configured approval chain will be applied.</p></div><button class="btn-primary">Submit for approval</button></div>
            </form>
<?php endif; ?>
        <?php endif; ?>

        <?php if(auth()->user()->can('payment_authorisations.verify') && app(\App\Services\ApprovalService::class)->canVerify($authorisation, auth()->user())): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payment_authorisations.verify')): ?>
<form method="POST" action="<?php echo e(route('payment-authorisations.verify', $authorisation)); ?>" class="mt-5 border-t border-gray-100 pt-5">
                <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
                <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-800">1</span><div><p class="text-sm font-semibold text-gray-900">Verification required</p><p class="text-xs text-gray-500">Check the selected schedules before forwarding them.</p></div></div>
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)_auto]"><select name="decision" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><option value="approved">Verify and forward</option><option value="modified_approved">Return for modification</option><option value="rejected">Reject</option></select><input name="note" placeholder="Add a comment (required when returning or rejecting)" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><button class="btn-primary whitespace-nowrap">Record verification</button></div>
            </form>
<?php endif; ?>
        <?php endif; ?>

        <?php if(auth()->user()->can('payment_authorisations.approve') && app(\App\Services\ApprovalService::class)->canApprove($authorisation, auth()->user())): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payment_authorisations.approve')): ?>
<form method="POST" action="<?php echo e(route('payment-authorisations.approve', $authorisation)); ?>" class="mt-5 border-t border-gray-100 pt-5">
                <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
                <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-800">2</span><div><p class="text-sm font-semibold text-gray-900">Final approval required</p><p class="text-xs text-gray-500">Approve the payment file or return it with a comment.</p></div></div>
                <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1.25fr)_auto]"><select name="decision" class="rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="approved">Approve authorisation</option><option value="modified_approved">Return for modification</option><option value="rejected">Reject</option></select><input name="note" placeholder="Add a comment (required when returning or rejecting)" class="rounded-lg border border-gray-300 px-3 py-2.5 text-sm"><button class="btn-primary whitespace-nowrap">Record approval</button></div>
            </form>
<?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payment_authorisations.view')): ?>
<a href="<?php echo e(route('payment-authorisations.index')); ?>" class="btn-quiet">← Back to payment authorisations</a>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/payment-authorisations/show.blade.php ENDPATH**/ ?>