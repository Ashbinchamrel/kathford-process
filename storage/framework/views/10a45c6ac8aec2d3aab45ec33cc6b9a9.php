<?php $__env->startSection('title', 'Payment Schedule'); ?>
<?php $__env->startSection('page-title', 'Payment Schedule'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-[1600px] space-y-3">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.create')): ?><div class="flex justify-end gap-3"><a href="<?php echo e(route('payments.create')); ?>" class="btn-primary">+ Add payment</a></div><?php endif; ?>

    <?php
        $paymentTabs = ['' => 'All active payments', 'pending_finance' => 'Awaiting Finance', 'scheduled' => 'Scheduled'];
    ?>
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="status" value="<?php echo e(request('status')); ?>">
            <label class="block flex-1 min-w-[220px]"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find a payment</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Payment, activity, bill, vendor or account" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label>
            <button class="btn-primary h-[42px] justify-center">Apply filters</button>
            <?php if(request()->filled('search') || request()->filled('status')): ?><a href="<?php echo e(route('payments.index')); ?>" class="h-[42px] px-3 py-3 text-center text-sm font-medium text-gray-500 hover:text-gray-800">Reset</a><?php endif; ?>
        </form>
        <div class="mt-4 -mx-1 overflow-x-auto px-1"><div class="flex min-w-max gap-1.5 border-t border-gray-100 pt-3">
            <?php $__currentLoopData = $paymentTabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('payments.index', array_merge(request()->except('status','page'), $value ? ['status' => $value] : []))); ?>" <?php if((string)request('status','') === $value): ?> aria-current="page" <?php endif; ?> class="rounded-lg px-3 py-2 text-xs font-semibold transition <?php echo e((string)request('status','') === $value ? 'bg-[#0B1E3D] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'); ?>"><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div></div>
    </section>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.import_vendor_invoices')): ?>
    <details class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm"><summary class="cursor-pointer text-sm font-semibold text-teal-700">Import pending vendor invoices from Excel</summary><div class="mt-3">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-teal-700">Pending vendor payments</p>
                <h2 class="mt-1 text-base font-semibold text-slate-900">Import pending vendor invoices</h2>
                <p class="mt-1 text-sm text-slate-500">Upload the outstanding invoice details only. Finance then opens each invoice and uses the normal payment process to set the account, TDS, and full or partial payment periods.</p>
            </div>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.import_vendor_invoices')): ?>
<a href="<?php echo e(route('payments.import-template')); ?>" class="btn-secondary shrink-0">Download template</a>
<?php endif; ?>
        </div>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.import_vendor_invoices')): ?>
<form method="POST" action="<?php echo e(route('payments.import')); ?>" enctype="multipart/form-data" class="mt-5 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-end">
            <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
            <label class="block flex-1"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Payment schedule file</span><input type="file" name="payment_import" accept=".csv,.txt,.xlsx,.xls" required class="block w-full rounded-lg border border-gray-300 bg-white text-sm text-gray-600 file:mr-3 file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-teal-800 hover:file:bg-teal-100"></label>
            <button class="btn-primary shrink-0">Import payment schedules</button>
        </form>
<?php endif; ?>
        <p class="mt-3 text-xs text-gray-500">Formats: CSV, XLSX, XLS · Max 5 MB. Required columns: Invoice Number, Vendor Email, Invoice Date, Gross Amount. Vendor Email must match an active vendor.</p>
    </div></details>
    <?php endif; ?>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
            <div><h2 class="font-semibold text-gray-900">Payment queue</h2><p class="mt-0.5 text-sm text-gray-500"><?php echo e($payments->total()); ?> payable record<?php echo e($payments->total() === 1 ? '' : 's'); ?> awaiting finance action</p></div>
        </div>
        <?php if (isset($component)) { $__componentOriginal0603e3dd4f4a876f40524d6d16d7452a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.super-admin-bulk-delete','data' => ['module' => 'payments']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('super-admin-bulk-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => 'payments']); ?>
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
                        <?php if(auth()->user()->isSuperAdmin()): ?><th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all payments" data-bulk-delete-toggle="payments"></th><?php endif; ?>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Process / Account</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Account Name</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Scheduled Amount</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Payment Type</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Schedule Period</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php
                        $paymentGroups = $payments->getCollection()->groupBy(fn ($payment) => $payment->paymentAccount?->name ?: $payment->account_name ?: 'Unassigned account');
                    ?>
                    <?php $__empty_1 = true; $__currentLoopData = $paymentGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $accountName => $accountPayments): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr><td colspan="<?php echo e(auth()->user()->isSuperAdmin() ? 9 : 8); ?>" class="border-y border-slate-200 bg-slate-50 px-3 py-2 text-xs font-bold uppercase tracking-wide text-slate-600"><?php echo e($accountName); ?></td></tr>
                    <?php $__currentLoopData = $accountPayments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pmt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $hasScheduleRows = $pmt->schedules->isNotEmpty();
                        $pendingScheduleRows = $pmt->schedules
                            ->filter(fn ($schedule) => $schedule->status === 'scheduled' && ! $schedule->payment_authorisation_id)
                            ->values();
                        $scheduledAmount = $pmt->status === 'pending_finance' ? 0 : ($hasScheduleRows ? (float) $pendingScheduleRows->sum('net_amount') : (float) ($pmt->net_amount ?: $pmt->amount_due));
                        $periodLabel = 'Not scheduled';

                        if ($pmt->status !== 'pending_finance') {
                            $periodLabel = $hasScheduleRows
                                ? $pendingScheduleRows->count().' pending period'.($pendingScheduleRows->count() === 1 ? '' : 's')
                                : (($pmt->schedule_month?->format('M Y') ?? '—').' · W'.($pmt->schedule_week ?? '—'));
                        }
                    ?>
                    <tr class="hover:bg-gray-50">
                        <?php if(auth()->user()->isSuperAdmin()): ?><td class="px-3 py-3"><input type="checkbox" value="<?php echo e($pmt->id); ?>" aria-label="Select <?php echo e($pmt->payment_number); ?>" data-bulk-delete-record="payments"></td><?php endif; ?>
                        <td class="px-3 py-2">
                            <?php if($pmt->purchaseOrder): ?><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('purchase_orders.view')): ?>
<a href="<?php echo e(route('purchase-orders.show', $pmt->purchaseOrder)); ?>" class="text-teal-600 hover:underline font-mono text-xs font-bold"><?php echo e($pmt->purchaseOrder->po_number); ?></a>
<?php endif; ?>
<?php elseif($pmt->activity_form_id): ?>
<p class="font-semibold text-teal-700"><?php echo e($pmt->activity_reference ?: 'Activity payment'); ?></p><p class="text-xs text-gray-600"><?php echo e($pmt->activity_name); ?></p>
<?php else: ?><p class="font-mono text-xs font-bold text-gray-700">External payment</p><?php endif; ?>
                            <p class="text-gray-500 text-xs mt-0.5"><?php echo e($pmt->payment_number); ?> · <?php echo e($pmt->vendor?->name ?: $pmt->account_name ?: ($pmt->activity_form_id ? 'Activity payment' : 'No vendor')); ?></p>
                        </td>
                        <td class="px-3 py-2 text-gray-600"><?php echo e($pmt->paymentAccount?->name ?: $pmt->account_name ?: '—'); ?></td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-gray-800 whitespace-nowrap">Rs <?php echo e(number_format($pmt->amount_due, 2)); ?></td>
                        <td class="px-3 py-2 text-right whitespace-nowrap">
                            <?php if($pmt->status === 'pending_finance'): ?>
                                <p class="text-sm font-medium text-amber-700">Not scheduled</p>
                            <?php else: ?>
                                <p class="font-mono font-semibold text-teal-800">Rs <?php echo e(number_format($scheduledAmount, 2)); ?></p>
                                <?php if($pmt->tds_applied): ?>
                                    <p class="mt-0.5 text-xs text-gray-400">Net of TDS · Rs <?php echo e(number_format($pmt->net_amount, 2)); ?> payable</p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-gray-600"><?php echo e(ucfirst($pmt->payment_type ?: 'full')); ?></td>
                        <td class="px-3 py-2 text-gray-600">
                            <?php if($pmt->status !== 'pending_finance' && $hasScheduleRows): ?>
                                <?php $__currentLoopData = $pendingScheduleRows->sortBy('scheduled_date'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $period): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p class="whitespace-nowrap"><?php echo e($period->schedule_month?->format('M Y')); ?> · Week <?php echo e($period->schedule_week); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php else: ?> <?php echo e($periodLabel); ?> <?php endif; ?>
                        </td>
                        <td class="px-3 py-2">
                            <?php if($pmt->status === 'paid'): ?>
                                <span class="px-2 py-0.5 rounded-full whitespace-nowrap text-xs font-medium bg-green-100 text-green-700">Paid</span>
                            <?php elseif($pmt->status === 'pending_finance'): ?>
                                <span class="px-2 py-0.5 rounded-full whitespace-nowrap text-xs font-medium bg-amber-100 text-amber-700">Awaiting Finance</span>
                            <?php elseif($pmt->status === 'in_authorisation'): ?>
                                <span class="px-2 py-0.5 rounded-full whitespace-nowrap text-xs font-medium bg-purple-100 text-purple-700">In Authorisation</span>
                            <?php elseif($pmt->status === 'authorised'): ?>
                                <span class="px-2 py-0.5 rounded-full whitespace-nowrap text-xs font-medium bg-teal-100 text-teal-700">Authorised</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 rounded-full whitespace-nowrap text-xs font-medium bg-blue-100 text-blue-700">Scheduled</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-right space-x-3">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.view')): ?>
<a href="<?php echo e(route('payments.show', $pmt)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View</a>
<?php endif; ?>
                            <?php if(in_array($pmt->status, ['pending_finance', 'scheduled'])): ?>
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payments.edit')): ?>
<a href="<?php echo e(route('payments.edit', $pmt)); ?>" class="text-gray-500 hover:text-gray-700 text-sm"><?php echo e($hasScheduleRows ? 'Process' : (in_array($pmt->source, ['imported_vendor', 'direct_activity_form']) ? 'Schedule' : 'Edit')); ?></a>
<?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e(auth()->user()->isSuperAdmin() ? 9 : 8); ?>" class="px-5 py-12 text-center text-gray-400">No payments are awaiting scheduling or Payment Authorisation.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($payments->hasPages()): ?>
            <div class="px-3 py-2 border-t border-gray-100"><?php echo e($payments->withQueryString()->links()); ?></div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/payments/index.blade.php ENDPATH**/ ?>