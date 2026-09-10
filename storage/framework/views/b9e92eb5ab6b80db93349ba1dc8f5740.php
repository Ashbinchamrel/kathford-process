<?php $__env->startSection('title', 'Purchase Requests'); ?>
<?php $__env->startSection('page-title', 'Purchase Requests'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-4">

    
    <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <input type="text" name="search" value="<?php echo e(request('search')); ?>"
                   placeholder="Search by PR number or title…"
                   class="flex-1 min-w-[200px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            <button type="submit" class="btn-primary">Search</button>
        </form>
        <a href="<?php echo e(route('purchase-requests.create')); ?>"
           class="flex items-center gap-2 px-5 py-2 rounded-lg text-sm font-semibold text-white whitespace-nowrap"
           style="background:#0B1E3D;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            New PR
        </a>
    </div>

    
    <?php
        $tabs = [
            ''                    => 'All',
            'draft'               => 'Draft',
            'pending_verification'=> 'Awaiting Verification',
            'pending_approval'    => 'Awaiting Approval',
            'approved'            => 'Approved',
            'rejected'            => 'Rejected',
            'in_rfq'              => 'In RFQ',
            'ordered'             => 'Ordered',
            'received'            => 'Received',
            'closed'              => 'Closed',
        ];
        $currentStatus = request('status', '');
    ?>
    <div class="flex flex-wrap gap-2">
        <?php $__currentLoopData = $tabs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('purchase-requests.index', array_merge(request()->except('status','page'), $value ? ['status'=>$value] : []))); ?>"
           class="px-3 py-1.5 rounded-full text-xs font-semibold border transition-colors
               <?php echo e($currentStatus === $value
                   ? 'border-transparent text-white'
                   : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50'); ?>"
           <?php if($currentStatus === $value): ?> style="background:#0B1E3D" <?php endif; ?>>
            <?php echo e($label); ?>

        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    
    <?php if(session('success')): ?>
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <?php echo e(session('success')); ?>

    </div>
    <?php endif; ?>

    
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">PR Number</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Linked Activity</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Requested By</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Est. Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php $__empty_1 = true; $__currentLoopData = $requests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $approvalService = app(\App\Services\ApprovalService::class);
                        $needsAction = $approvalService->canVerify($pr, auth()->user()) || $approvalService->canApprove($pr, auth()->user());
                        $statusColors = [
                            'draft'               => 'bg-gray-100 text-gray-600',
                            'pending_verification'=> 'bg-blue-100 text-blue-700',
                            'verified'            => 'bg-teal-100 text-teal-700',
                            'pending_approval'    => 'bg-amber-100 text-amber-700',
                            'approved'            => 'bg-green-100 text-green-700',
                            'rejected'            => 'bg-red-100 text-red-700',
                            'in_rfq'              => 'bg-purple-100 text-purple-700',
                            'ordered'             => 'bg-indigo-100 text-indigo-700',
                            'received'            => 'bg-emerald-100 text-emerald-700',
                            'closed'              => 'bg-gray-100 text-gray-500',
                        ];
                        $statusLabels = [
                            'pending_verification' => 'Awaiting Verification',
                            'pending_approval'     => 'Awaiting Approval',
                        ];
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors <?php echo e($needsAction ? 'bg-amber-50 hover:bg-amber-100' : ''); ?>">
                        <td class="px-5 py-3 font-mono text-xs font-bold text-gray-700">
                            <?php echo e($pr->form_number); ?>

                            <?php if($needsAction): ?>
                                <span class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-400"></span>
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 font-medium text-gray-900 max-w-xs truncate"><?php echo e($pr->title); ?></td>
                        <td class="px-5 py-3 text-gray-500 text-xs">
                            <?php if($pr->activityForm): ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?>
<a href="<?php echo e(route('activity-forms.show', $pr->activityForm)); ?>" class="text-teal-600 hover:underline font-mono"><?php echo e($pr->activityForm->form_number); ?></a>
<?php endif; ?>
                            <?php else: ?> —
                            <?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-gray-600"><?php echo e($pr->creator?->name ?? '—'); ?></td>
                        <td class="px-5 py-3 text-right font-mono text-gray-700">
                            Rs <?php echo e(number_format($pr->total_amount, 2)); ?>

                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo e($statusColors[$pr->status] ?? 'bg-gray-100 text-gray-600'); ?>">
                                <?php echo e($statusLabels[$pr->status] ?? ucfirst(str_replace('_', ' ', $pr->status))); ?>

                            </span>
                            <?php if($pr->approvalChain): ?><p class="mt-1 max-w-40 truncate text-xs text-gray-400" title="<?php echo e($pr->approvalChain->name); ?>"><?php echo e($pr->approvalChain->name); ?></p><?php endif; ?>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-400"><?php echo e($pr->created_at->format('d M Y')); ?></td>
                        <td class="px-5 py-3 text-right">
                            <a href="<?php echo e(route('purchase-requests.show', $pr)); ?>"
                               class="text-sm font-medium <?php echo e($needsAction ? 'text-amber-700 hover:text-amber-900' : 'text-teal-600 hover:text-teal-700'); ?>">
                                <?php echo e($needsAction ? 'Act Now →' : 'View →'); ?>

                            </a>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-gray-400 text-sm">
                            <?php if(request('status')): ?>
                                No purchase requests with this status.
                            <?php else: ?>
                                No purchase requests yet. <a href="<?php echo e(route('purchase-requests.create')); ?>" class="text-teal-600 hover:underline">Create one now</a>.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($requests->hasPages()): ?>
        <div class="px-5 py-3 border-t border-gray-100">
            <?php echo e($requests->withQueryString()->links()); ?>

        </div>
        <?php endif; ?>
    </div>

    
    <div class="flex items-center gap-2 text-xs text-gray-400">
        <span class="inline-block w-2 h-2 rounded-full bg-amber-400"></span>
        <span>Rows highlighted in amber require your action</span>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/purchase-requests/index.blade.php ENDPATH**/ ?>