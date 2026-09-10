<?php $__env->startSection('title', 'Procurement Checklists'); ?>
<?php $__env->startSection('page-title', 'Procurement Checklists'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-[1600px] space-y-3">
    
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><form method="GET" class="flex flex-wrap items-end gap-3"><input type="hidden" name="status" value="<?php echo e(request('status')); ?>"><label class="block flex-1 min-w-[200px]"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find a checklist</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="PO number, bill or vendor" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label><button class="btn-primary h-[42px]">Apply filters</button></form><div class="mt-4 flex flex-wrap gap-1.5 border-t border-gray-100 pt-3"><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('checklists.view')): ?>
<a href="<?php echo e(route('checklists.index', request()->except('status','page'))); ?>" class="rounded-lg px-3 py-2 text-xs font-semibold <?php echo e(!request('status') ? 'bg-[#0B1E3D] text-white' : 'text-gray-600 hover:bg-gray-100'); ?>">All checklists</a>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('checklists.view')): ?>
<a href="<?php echo e(route('checklists.index', array_merge(request()->except('status','page'), ['status' => 'pending_controls']))); ?>" class="rounded-lg px-3 py-2 text-xs font-semibold <?php echo e(request('status') === 'pending_controls' ? 'bg-[#0B1E3D] text-white' : 'text-gray-600 hover:bg-gray-100'); ?>">Pending controls</a>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('checklists.view')): ?>
<a href="<?php echo e(route('checklists.index', array_merge(request()->except('status','page'), ['status' => 'ready_for_accounts']))); ?>" class="rounded-lg px-3 py-2 text-xs font-semibold <?php echo e(request('status') === 'ready_for_accounts' ? 'bg-[#0B1E3D] text-white' : 'text-gray-600 hover:bg-gray-100'); ?>">Ready for Accounts</a>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('checklists.view')): ?>
<a href="<?php echo e(route('checklists.index', array_merge(request()->except('status','page'), ['status' => 'sent_to_accounts']))); ?>" class="rounded-lg px-3 py-2 text-xs font-semibold <?php echo e(request('status') === 'sent_to_accounts' ? 'bg-[#0B1E3D] text-white' : 'text-gray-600 hover:bg-gray-100'); ?>">Sent to Accounts</a>
<?php endif; ?></div></section>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm"><div class="flex items-center justify-between border-b border-gray-100 px-3 py-2"><div><h2 class="font-semibold text-gray-800">Vendor bills awaiting controls</h2><p class="mt-1 text-sm text-gray-500"><?php echo e($checklists->total()); ?> bill<?php echo e($checklists->total() === 1 ? '' : 's'); ?> in this view.</p></div></div><?php if (isset($component)) { $__componentOriginal0603e3dd4f4a876f40524d6d16d7452a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.super-admin-bulk-delete','data' => ['module' => 'checklists']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('super-admin-bulk-delete'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => 'checklists']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a)): ?>
<?php $attributes = $__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a; ?>
<?php unset($__attributesOriginal0603e3dd4f4a876f40524d6d16d7452a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal0603e3dd4f4a876f40524d6d16d7452a)): ?>
<?php $component = $__componentOriginal0603e3dd4f4a876f40524d6d16d7452a; ?>
<?php unset($__componentOriginal0603e3dd4f4a876f40524d6d16d7452a); ?>
<?php endif; ?><div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-200 text-sm"><thead class="bg-gray-50"><tr><?php if(auth()->user()->isSuperAdmin()): ?><th class="w-10 px-3 py-3"><input type="checkbox" aria-label="Select all checklists" data-bulk-delete-toggle="checklists"></th><?php endif; ?><th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">PO / Bill</th><th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th><th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Type</th><th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 uppercase">Amount</th><th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Status</th><th class="px-3 py-2"></th></tr></thead><tbody class="divide-y divide-gray-100"><?php $__empty_1 = true; $__currentLoopData = $checklists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $checklist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr class="hover:bg-gray-50"><?php if(auth()->user()->isSuperAdmin()): ?><td class="px-3 py-3"><input type="checkbox" value="<?php echo e($checklist->id); ?>" aria-label="Select checklist" data-bulk-delete-record="checklists"></td><?php endif; ?><td class="px-3 py-2"><p class="font-mono text-xs font-bold text-gray-700"><?php echo e($checklist->purchaseOrder?->po_number); ?></p><p class="mt-1 text-sm text-gray-700">Bill: <?php echo e($checklist->vendorBill?->bill_number); ?></p></td><td class="px-3 py-2 text-gray-700"><?php echo e($checklist->vendor?->name); ?></td><td class="px-3 py-2 capitalize text-gray-600"><?php echo e($checklist->fulfillment_type); ?></td><td class="px-3 py-2 text-right font-mono text-gray-800">Rs <?php echo e(number_format($checklist->vendorBill?->amount ?? 0, 2)); ?></td><td class="px-3 py-2"><span class="rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($checklist->status === 'sent_to_accounts' ? 'bg-green-100 text-green-800' : ($checklist->status === 'ready_for_accounts' ? 'bg-teal-100 text-teal-800' : 'bg-amber-100 text-amber-800')); ?>"><?php echo e($checklist->statusLabel()); ?></span></td><td class="px-3 py-2 text-right"><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('checklists.view')): ?>
<a href="<?php echo e(route('checklists.show', $checklist)); ?>" class="font-semibold text-teal-700 hover:text-teal-800">Review →</a>
<?php endif; ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="<?php echo e(auth()->user()->isSuperAdmin() ? 7 : 6); ?>" class="px-5 py-14 text-center text-gray-400">No vendor bill checklists found.</td></tr><?php endif; ?></tbody></table></div><?php if($checklists->hasPages()): ?><div class="px-3 py-2 border-t border-gray-100"><?php echo e($checklists->links()); ?></div><?php endif; ?></div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/checklists/index.blade.php ENDPATH**/ ?>