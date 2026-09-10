<?php $__env->startSection('title', 'Payment Authorisation'); ?>
<?php $__env->startSection('page-title', 'Payment Authorisation'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-[1600px] space-y-3">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payment_authorisations.create')): ?><div class="flex justify-end gap-3"><a href="<?php echo e(route('payment-authorisations.create')); ?>" class="btn-primary">+ New authorisation</a></div><?php endif; ?>
    <?php
        $tabs = ['' => 'All authorisations', 'generated' => 'Ready to submit', 'pending_verification' => 'Verification', 'pending_approval' => 'Approval', 'approved' => 'Approved', 'rejected' => 'Returned / rejected'];
        $currentStatus = (string) request('status', '');
    ?>
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_210px_auto] lg:items-end">
            <input type="hidden" name="status" value="<?php echo e($currentStatus); ?>">
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find an authorisation</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Authorisation number, channel or approval chain" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Scheduled month</span><input type="month" name="month" value="<?php echo e(request('month')); ?>" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm"></label>
            <div class="flex gap-2"><button class="btn-primary h-[42px] justify-center">Apply filters</button><?php if(request()->filled('search') || request()->filled('month') || request()->filled('status')): ?><a href="<?php echo e(route('payment-authorisations.index')); ?>" class="h-[42px] px-3 py-3 text-sm text-gray-500">Reset</a><?php endif; ?></div>
        </form>
        <div class="mt-4 -mx-1 overflow-x-auto px-1"><div class="flex min-w-max gap-1.5 border-t border-gray-100 pt-3">
            <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('payment-authorisations.index', array_merge(request()->except('status','page'), $value ? ['status' => $value] : []))); ?>" <?php if($currentStatus === $value): ?> aria-current="page" <?php endif; ?> class="rounded-lg px-3 py-2 text-xs font-semibold transition <?php echo e($currentStatus === $value ? 'bg-[#0B1E3D] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'); ?>"><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div></div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
            <div>
                <h2 class="font-semibold text-gray-900">Authorisation queue</h2>
                <p class="mt-0.5 text-sm text-gray-500"><?php echo e($authorisations->total()); ?> authorisation<?php echo e($authorisations->total() === 1 ? '' : 's'); ?></p>
            </div>
        </div>

        <?php if (isset($component)) { $__componentOriginal0603e3dd4f4a876f40524d6d16d7452a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.super-admin-bulk-delete','data' => ['module' => 'payment_authorisations']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('super-admin-bulk-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => 'payment_authorisations']); ?>
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
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                    <tr>
                        <?php if(auth()->user()->isSuperAdmin()): ?>
                            <th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all authorisations" data-bulk-delete-toggle="payment_authorisations"></th>
                        <?php endif; ?>
                        <th class="px-3 py-2">Authorisation</th>
                        <th class="px-3 py-2">Channel / chain</th>
                        <th class="px-3 py-2">Scheduled period</th>
                        <th class="px-3 py-2 text-right">Payments</th>
                        <th class="px-3 py-2 text-right">Total</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2"><span class="sr-only">Open</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $authorisations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $authorisation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="transition hover:bg-slate-50">
                            <?php if(auth()->user()->isSuperAdmin()): ?>
                                <td class="px-3 py-4"><input type="checkbox" value="<?php echo e($authorisation->id); ?>" aria-label="Select <?php echo e($authorisation->authorisation_number); ?>" data-bulk-delete-record="payment_authorisations"></td>
                            <?php endif; ?>
                            <td class="px-3 py-2"><p class="font-mono text-xs font-bold text-slate-900"><?php echo e($authorisation->authorisation_number); ?></p><p class="mt-1 text-xs text-slate-400">Created <?php echo e($authorisation->created_at?->format('d M Y')); ?></p></td>
                            <td class="px-3 py-2"><p class="font-medium text-slate-900"><?php echo e($authorisation->paymentAuthorisationChannel?->name ?: 'Legacy channel'); ?></p><p class="mt-1 text-xs text-slate-400"><?php echo e($authorisation->approvalChain?->name ?: 'No active chain'); ?></p></td>
                            <td class="px-3 py-2 text-slate-700"><?php echo e($authorisation->schedule_month?->format('F Y') ?: '—'); ?> · Week <?php echo e($authorisation->schedule_week); ?></td>
                            <td class="px-3 py-2 text-right font-mono text-slate-700"><?php echo e($authorisation->payments->count()); ?></td>
                            <td class="px-3 py-2 text-right font-mono font-semibold text-slate-900">Rs <?php echo e(number_format($authorisation->total_amount, 2)); ?></td>
                            <td class="px-3 py-2">
                                <?php ($statusClass = match($authorisation->status) { 'approved' => 'bg-emerald-100 text-emerald-800', 'pending_verification', 'pending_approval' => 'bg-amber-100 text-amber-800', 'rejected' => 'bg-rose-100 text-rose-800', default => 'bg-slate-100 text-slate-700' }); ?>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($statusClass); ?>"><?php echo e($authorisation->statusLabel()); ?></span>
                                <?php if($step = app(\App\Services\ApprovalService::class)->pendingStepLabel($authorisation)): ?><p class="mt-1 text-xs text-amber-800"><?php echo e($step); ?></p><?php endif; ?>
                            </td>
                            <td class="px-3 py-2 text-right"><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('payment_authorisations.view')): ?>
<a href="<?php echo e(route('payment-authorisations.show', $authorisation)); ?>" class="font-semibold text-teal-700 hover:text-teal-800">View <span aria-hidden="true">→</span></a>
<?php endif; ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="<?php echo e(auth()->user()->isSuperAdmin() ? 8 : 7); ?>" class="px-5 py-14 text-center text-sm text-gray-400">No authorisations match these filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($authorisations->hasPages()): ?>
            <div class="border-t border-gray-100 px-3 py-2"><?php echo e($authorisations->links()); ?></div>
        <?php endif; ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/payment-authorisations/index.blade.php ENDPATH**/ ?>