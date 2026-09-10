<?php $__env->startSection('title', 'RFQ / Quotations'); ?>
<?php $__env->startSection('page-title', 'RFQs & Quotations'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-[1600px] space-y-3">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.create')): ?><div class="flex justify-end gap-3"><a href="<?php echo e(route('rfq.create')); ?>" class="btn-primary">+ New RFQ</a></div><?php endif; ?>

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-3 flex-1 flex-wrap items-end"><input type="hidden" name="status" value="<?php echo e(request('status')); ?>"><label class="block flex-1 min-w-[200px]"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find an RFQ</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="RFQ number or title" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label><button class="btn-primary h-[42px]">Apply filters</button></form>
    </div>

    
    <?php
        $tabs = [
            ''          => 'All',
            'draft'     => 'Draft',
            'sent'      => 'Invitations Sent',
            'quotes_received' => 'Quotes Received',
            'partially_awarded' => 'Partially Awarded',
            'items_awarded' => 'All Items Awarded',
            'closed'    => 'Closed',
            'cancelled' => 'Cancelled',
        ];
        $currentStatus = request('status', '');
    ?>
    <div class="mt-4 -mx-1 overflow-x-auto border-t border-gray-100 px-1 pt-3"><div class="flex min-w-max gap-1.5">
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.index', array_merge(request()->except('status','page'), $value ? ['status'=>$value] : []))); ?>"
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.super-admin-bulk-delete','data' => ['module' => 'rfqs']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('super-admin-bulk-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => 'rfqs']); ?>
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
                        <?php if(auth()->user()->isSuperAdmin()): ?><th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all RFQs" data-bulk-delete-toggle="rfqs"></th><?php endif; ?>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">RFQ Number</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Activity Form</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendors</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Deadline</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Created By</th>
                        <th class="relative px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $rfqs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rfq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $statusColors = [
                            'draft'     => 'bg-gray-100 text-gray-600',
                            'open'      => 'bg-blue-100 text-blue-700',
                            'sent'      => 'bg-blue-100 text-blue-700',
                            'quotes_received' => 'bg-indigo-100 text-indigo-700',
                            'partially_awarded' => 'bg-amber-100 text-amber-700',
                            'items_awarded' => 'bg-green-100 text-green-700',
                            'closed'    => 'bg-green-100 text-green-700',
                            'cancelled' => 'bg-red-100 text-red-700',
                        ];
                        $submitted = $rfq->quotes->where('status', 'submitted')->count();
                        $total     = $rfq->quotes->count();
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <?php if(auth()->user()->isSuperAdmin()): ?><td class="px-3 py-3"><input type="checkbox" value="<?php echo e($rfq->id); ?>" aria-label="Select <?php echo e($rfq->rfq_number); ?>" data-bulk-delete-record="rfqs"></td><?php endif; ?>
                        <td class="px-3 py-2 font-mono text-xs font-bold text-gray-700"><?php echo e($rfq->rfq_number); ?></td>
                        <td class="px-3 py-2 font-medium text-gray-900 max-w-xs truncate">
                            <?php echo e($rfq->title ?? '—'); ?>

                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500">
                            <?php if($rfq->activityForm): ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?>
<a href="<?php echo e(route('activity-forms.show', $rfq->activityForm)); ?>" class="text-teal-600 hover:underline font-mono">
                                    <?php echo e($rfq->activityForm->form_number); ?>

                                </a>
<?php endif; ?>
                            <?php else: ?>
                                <span class="text-gray-400 italic">Standalone</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-600">
                            <?php echo e($total); ?> vendor<?php echo e($total !== 1 ? 's' : ''); ?>

                            <?php if($submitted > 0): ?>
                                <span class="ml-1 text-teal-600 font-semibold">(<?php echo e($submitted); ?> quoted)</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-400">
                            <?php if($rfq->deadline): ?>
                                <span class="<?php echo e($rfq->deadline->isPast() && $rfq->status !== 'closed' ? 'text-red-500 font-semibold' : ''); ?>">
                                    <?php echo e($rfq->deadline->format('d M Y')); ?>

                                </span>
                            <?php else: ?> —
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo e($statusColors[$rfq->status] ?? 'bg-gray-100 text-gray-600'); ?>">
                                <?php echo e($rfq->statusLabel()); ?>

                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs text-gray-500"><?php echo e($rfq->createdBy?->name ?? '—'); ?></td>
                        <td class="px-3 py-2 text-right">
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.show', $rfq)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View →</a>
<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="<?php echo e(auth()->user()->isSuperAdmin() ? 9 : 8); ?>" class="px-5 py-12 text-center text-gray-400 text-sm">
                            No RFQs found. <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.create')): ?>
<a href="<?php echo e(route('rfq.create')); ?>" class="text-teal-600 hover:underline">Create one now</a>
<?php endif; ?>.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($rfqs->hasPages()): ?>
        <div class="px-3 py-2 border-t border-gray-100">
            <?php echo e($rfqs->withQueryString()->links()); ?>

        </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/rfq/index.blade.php ENDPATH**/ ?>