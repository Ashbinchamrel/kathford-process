<?php $__env->startSection('title', 'Activity Forms'); ?>
<?php $__env->startSection('page-title', 'Activity Forms'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $tabs = ['' => 'All forms', 'draft' => 'Drafts', 'pending_verification' => 'Verification', 'verified' => 'Verified', 'pending_approval' => 'Approval', 'approved' => 'Approved', 'rejected' => 'Returned'];
    $currentStatus = request('status', '');
    $statusStyle = fn ($status) => match ($status) {
        'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-100',
        'pending_verification', 'pending_approval' => 'bg-amber-50 text-amber-800 ring-amber-100',
        'verified' => 'bg-sky-50 text-sky-700 ring-sky-100',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
?>

<div class="mx-auto max-w-[1600px] space-y-3">
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.create')): ?><div class="flex justify-end gap-3"><a href="<?php echo e(route('activity-forms.create')); ?>" class="btn-primary">+ New activity form</a></div><?php endif; ?>

    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form method="GET" class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_280px_auto_auto] lg:items-end">
            <input type="hidden" name="status" value="<?php echo e($currentStatus); ?>">
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find a form</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Form number or activity name" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label>
            <label class="block"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Category</span><select name="category_id" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"><option value="">All categories</option><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($cat->id); ?>" <?php if(request('category_id') == $cat->id): echo 'selected'; endif; ?>><?php echo e($cat->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <button class="btn-primary h-[42px] justify-center">Apply filters</button>
            <?php if(request()->hasAny(['search','category_id','status'])): ?><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?>
<a href="<?php echo e(route('activity-forms.index')); ?>" class="h-[42px] px-3 py-3 text-center text-sm font-medium text-gray-500 hover:text-gray-800">Reset</a>
<?php endif; ?>
<?php endif; ?>
        </form>
        <div class="mt-4 -mx-1 overflow-x-auto px-1"><div class="flex min-w-max gap-1.5 border-t border-gray-100 pt-3"><?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?>
<a href="<?php echo e(route('activity-forms.index', array_merge(request()->except('status','page'), $value ? ['status' => $value] : []))); ?>" class="rounded-lg px-3 py-2 text-xs font-semibold transition <?php echo e($currentStatus === $value ? 'bg-[#0B1E3D] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'); ?>"><?php echo e($label); ?></a>
<?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></div>
    </section>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2"><div><h3 class="font-semibold text-gray-900"><?php echo e($tabs[$currentStatus] ?? 'Activity forms'); ?></h3><p class="mt-0.5 text-sm text-gray-500"><?php echo e($forms->total()); ?> record<?php echo e($forms->total() === 1 ? '' : 's'); ?> found</p></div><p class="hidden text-xs text-gray-400 sm:block">Amber markers require your action</p></div>
        <?php if (isset($component)) { $__componentOriginal0603e3dd4f4a876f40524d6d16d7452a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.super-admin-bulk-delete','data' => ['module' => 'activity_forms']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('super-admin-bulk-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => 'activity_forms']); ?>
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
        <div class="overflow-x-auto"><table class="min-w-[1040px] w-full text-sm"><thead class="border-b border-gray-100 bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wide text-slate-500"><tr><?php if(auth()->user()->isSuperAdmin()): ?><th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all activity forms" data-bulk-delete-toggle="activity_forms"></th><?php endif; ?><th class="w-40 px-3 py-2">Form</th><th class="min-w-[240px] px-3 py-2">Activity</th><th class="w-48 px-3 py-2">Category</th><th class="w-44 px-3 py-2">Workflow</th><th class="w-36 px-3 py-2">Deadline</th><th class="w-36 px-3 py-2 text-right">Amount</th><th class="w-28 px-3 py-2"></th></tr></thead><tbody class="divide-y divide-gray-100"><?php $__empty_1 = true; $__currentLoopData = $forms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $form): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php ($needsAction = ($form->isPendingVerification() && auth()->user()->can('verify', $form)) || ($form->isPendingApproval() && auth()->user()->can('finalApprove', $form))); ?><tr class="group transition hover:bg-slate-50 <?php echo e($needsAction ? 'bg-amber-50/40' : ''); ?>"><?php if(auth()->user()->isSuperAdmin()): ?><td class="px-3 py-4"><input type="checkbox" value="<?php echo e($form->id); ?>" aria-label="Select <?php echo e($form->form_number); ?>" data-bulk-delete-record="activity_forms"></td><?php endif; ?><td class="px-3 py-2"><div class="flex items-center gap-2"><span class="font-mono font-bold text-slate-800"><?php echo e($form->form_number); ?></span><?php if($needsAction): ?><span class="h-2 w-2 rounded-full bg-amber-400" title="Action required"></span><?php endif; ?></div><p class="mt-1 text-xs text-slate-400"><?php echo e($form->creator?->name ?? '—'); ?></p></td><td class="px-3 py-2"><p class="max-w-[320px] truncate font-semibold text-slate-800" title="<?php echo e($form->activity_name); ?>"><?php echo e($form->activity_name); ?></p><p class="mt-1 truncate text-xs text-slate-500"><?php echo e($form->department?->name ?? 'No department'); ?></p></td><td class="px-3 py-2"><span class="inline-flex max-w-[175px] truncate rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600" title="<?php echo e($form->category?->name); ?>"><?php echo e($form->category?->name ?? 'Uncategorised'); ?></span></td><td class="px-3 py-2"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset <?php echo e($statusStyle($form->status)); ?>"><?php echo e($form->statusLabel()); ?></span></td><td class="px-3 py-2 text-slate-600"><?php if($form->deadline_date): ?><p class="font-medium <?php echo e($form->deadline_date->isPast() && ! $form->isApproved() ? 'text-rose-600' : ''); ?>"><?php echo e($form->deadline_date->format('d M Y')); ?></p><?php if($form->deadline_date->isToday()): ?><p class="mt-1 text-xs text-amber-600">Due today</p><?php endif; ?> <?php else: ?> <span class="text-slate-400">No deadline</span> <?php endif; ?></td><td class="px-3 py-2 text-right"><span class="whitespace-nowrap font-mono font-semibold text-slate-800"><?php echo e($form->total_estimated_amount ? 'Rs '.number_format($form->total_estimated_amount, 2) : '—'); ?></span></td><td class="px-3 py-2 text-right"><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?>
<a href="<?php echo e(route('activity-forms.show', $form)); ?>" class="inline-flex items-center rounded-lg px-3 py-2 text-xs font-semibold <?php echo e($needsAction ? 'bg-amber-500 text-white hover:bg-amber-600' : 'bg-teal-50 text-teal-700 hover:bg-teal-100'); ?>"><?php echo e($needsAction ? 'Review' : 'Open'); ?> <span class="ml-1">→</span></a>
<?php endif; ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="<?php echo e(auth()->user()->isSuperAdmin() ? 8 : 7); ?>" class="px-5 py-16 text-center"><p class="font-medium text-slate-600">No matching activity forms</p><p class="mt-1 text-sm text-slate-400">Try changing the filters or create a new activity form.</p></td></tr><?php endif; ?></tbody></table></div>
        <?php if($forms->hasPages()): ?><div class="border-t border-gray-100 px-3 py-2"><?php echo e($forms->withQueryString()->links()); ?></div><?php endif; ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/activity-forms/index.blade.php ENDPATH**/ ?>