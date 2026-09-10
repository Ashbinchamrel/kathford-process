<?php $__env->startSection('title', $purchaseRequest->form_number); ?>
<?php $__env->startSection('page-title', 'Purchase Request'); ?>

<?php $__env->startSection('content'); ?>
<?php $pr = $purchaseRequest; ?>
<div class="max-w-4xl space-y-5">

    
    <?php if(session('success')): ?>
    <div class="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700 flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <?php echo e(session('success')); ?>

    </div>
    <?php endif; ?>

    
    <?php
        $wfSteps = [
            ['label' => 'Activity Form',    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'done' => true],
            ['label' => 'Purchase Request', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z', 'done' => false, 'active' => true],
            ['label' => 'RFQ',              'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'done' => in_array($pr->status, ['in_rfq','ordered','received','closed'])],
            ['label' => 'Purchase Order',   'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2', 'done' => in_array($pr->status, ['ordered','received','closed'])],
            ['label' => 'Checklist',              'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8l1 12h12L19 8', 'done' => in_array($pr->status, ['received','closed'])],
            ['label' => 'Payment',          'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'done' => $pr->status === 'closed'],
        ];
    ?>
    <div class="bg-white rounded-xl border border-gray-200 px-6 py-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Procurement Workflow</p>
        <div class="flex items-center gap-0 overflow-x-auto">
            <?php $__currentLoopData = $wfSteps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center flex-shrink-0">
                <div class="flex flex-col items-center text-center" style="min-width:72px">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center mb-1
                        <?php echo e(!empty($step['active']) ? 'text-white' : ($step['done'] ? 'bg-teal-500 text-white' : 'bg-gray-100 text-gray-400')); ?>"
                        <?php if(!empty($step['active'])): ?> style="background:#0B1E3D" <?php endif; ?>>
                        <?php if($step['done'] && empty($step['active'])): ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <?php else: ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo e($step['icon']); ?>"/></svg>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs font-medium leading-tight <?php echo e(!empty($step['active']) ? 'text-gray-900' : 'text-gray-400'); ?>"><?php echo e($step['label']); ?></p>
                </div>
                <?php if(!$loop->last): ?><div class="h-px w-8 bg-gray-200 flex-shrink-0 -mt-4"></div><?php endif; ?>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    
    <?php
        $bannerCfg = [
            'draft'               => ['bg-gray-50 border-gray-200 text-gray-600',     'Draft',                'Complete your request and submit it for approval.'],
            'pending_verification'=> ['bg-blue-50 border-blue-200 text-blue-700',     'Awaiting Verification','A verifier will review this request shortly.'],
            'verified'            => ['bg-teal-50 border-teal-200 text-teal-700',     'Verified',             'Verified and awaiting final approval.'],
            'pending_approval'    => ['bg-yellow-50 border-yellow-200 text-yellow-700','Awaiting Approval',    'An approver will make the final decision shortly.'],
            'approved'            => ['bg-green-50 border-green-200 text-green-700',  'Approved ✓',           'This request is approved. Finance can now create an RFQ.'],
            'rejected'            => ['bg-red-50 border-red-200 text-red-700',        'Rejected',             'This request was rejected. Please review remarks and resubmit.'],
            'in_rfq'              => ['bg-purple-50 border-purple-200 text-purple-700','In RFQ',              'Quotations are being collected from vendors.'],
            'ordered'             => ['bg-indigo-50 border-indigo-200 text-indigo-700','Ordered',             'A Purchase Order has been raised.'],
            'received'            => ['bg-emerald-50 border-emerald-200 text-emerald-700','Goods Received',   'Goods have been received and are pending payment.'],
            'closed'              => ['bg-gray-50 border-gray-200 text-gray-500',     'Closed',              'This request is fully processed and closed.'],
        ];
        [$bannerClass, $bannerTitle, $bannerMsg] = $bannerCfg[$pr->status] ?? ['bg-gray-50 border-gray-200 text-gray-600', ucfirst($pr->status), ''];
    ?>
    <div class="rounded-xl border px-5 py-4 <?php echo e($bannerClass); ?>">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide opacity-60 mb-0.5">Status</p>
                <p class="font-bold text-lg"><?php echo e($bannerTitle); ?></p>
                <?php if($bannerMsg): ?><p class="text-sm mt-0.5 opacity-80"><?php echo e($bannerMsg); ?></p><?php endif; ?>
            </div>
            <div class="text-right flex-shrink-0">
                <p class="text-xs font-semibold uppercase tracking-wide opacity-60">PR Number</p>
                <p class="font-mono font-bold text-lg"><?php echo e($pr->form_number); ?></p>
            </div>
        </div>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-4 text-sm uppercase tracking-wide">Request Details</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Title</dt>
                <dd class="text-gray-900 font-medium text-base"><?php echo e($pr->title); ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Requested By</dt>
                <dd class="text-gray-700"><?php echo e($pr->creator?->name ?? '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Department</dt>
                <dd class="text-gray-700"><?php echo e($pr->creator?->department?->name ?? '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Date Created</dt>
                <dd class="text-gray-700"><?php echo e($pr->created_at->format('d M Y')); ?></dd>
            </div>
            <div>
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Linked Activity Form</dt>
                <dd class="text-gray-700">
                    <?php if($pr->activityForm): ?>
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?>
<a href="<?php echo e(route('activity-forms.show', $pr->activityForm)); ?>" class="text-teal-600 hover:underline">
                            <?php echo e($pr->activityForm->form_number); ?> — <?php echo e($pr->activityForm->activity_name); ?>

                        </a>
<?php endif; ?>
                    <?php else: ?> —
                    <?php endif; ?>
                </dd>
            </div>
            <?php if($pr->description): ?>
            <div class="sm:col-span-2">
                <dt class="text-xs text-gray-400 font-semibold uppercase mb-1">Description / Justification</dt>
                <dd class="text-gray-700"><?php echo e($pr->description); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>

    
    <?php if($pr->approvalChain): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2"><h2 class="font-semibold text-gray-800 text-sm uppercase tracking-wide">Approval Chain — <?php echo e($pr->approvalChain->name); ?></h2><?php if($step = app(\App\Services\ApprovalService::class)->pendingStepLabel($pr)): ?><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Pending: <?php echo e($step); ?></span><?php endif; ?></div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Verifiers</p>
                <?php $__empty_1 = true; $__currentLoopData = $pr->approvalChain->verifiers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $v): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#1C3557;"><?php echo e(substr($v->name,0,1)); ?></div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 leading-tight">Verifier layer <?php echo e($loop->iteration); ?> · <?php echo e($v->name); ?></p>
                        <?php if($pr->verifier_id === $v->id): ?>
                            <p class="text-xs text-teal-600 font-semibold">✓ Verified</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-xs text-gray-400">None assigned</p>
                <?php endif; ?>
            </div>
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Approvers</p>
                <?php $__empty_1 = true; $__currentLoopData = $pr->approvalChain->approvers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="flex items-center gap-2 mb-2">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0" style="background:#00A99D;"><?php echo e(substr($a->name,0,1)); ?></div>
                    <div>
                        <p class="text-sm font-medium text-gray-800 leading-tight">Approver layer <?php echo e($loop->iteration); ?> · <?php echo e($a->name); ?></p>
                        <?php if($pr->approver_id === $a->id): ?>
                            <p class="text-xs text-green-600 font-semibold">✓ Approved</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-xs text-gray-400">None assigned</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">Requested Items</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Description</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Unit</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Est. Rate (Rs)</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Est. Amount (Rs)</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php $__currentLoopData = $pr->lineItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3 text-gray-400"><?php echo e($idx + 1); ?></td>
                        <td class="px-5 py-3 text-gray-800 font-medium"><?php echo e($item->description); ?></td>
                        <td class="px-5 py-3 text-gray-500"><?php echo e($item->unit ?? '—'); ?></td>
                        <td class="px-5 py-3 text-right font-mono"><?php echo e($item->quantity); ?></td>
                        <td class="px-5 py-3 text-right font-mono"><?php echo e($item->rate ? number_format($item->rate, 2) : '—'); ?></td>
                        <td class="px-5 py-3 text-right font-mono font-semibold"><?php echo e($item->amount ? number_format($item->amount, 2) : '—'); ?></td>
                        <td class="px-5 py-3 text-gray-500 text-xs"><?php echo e($item->vendor?->name ?? '—'); ?></td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
                <tfoot class="bg-gray-50">
                    <tr>
                        <td colspan="5" class="px-5 py-3 text-right text-sm font-bold text-gray-700">Estimated Total</td>
                        <td class="px-5 py-3 text-right font-mono font-bold text-gray-900 text-base">Rs <?php echo e(number_format($pr->total_amount, 2)); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    
    <?php if($pr->attachments && $pr->attachments->count()): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-3 text-sm uppercase tracking-wide">Attachments</h2>
        <ul class="space-y-2">
            <?php $__currentLoopData = $pr->attachments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <li class="flex items-center gap-3 text-sm">
                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <a href="<?php echo e(route('attachments.download', $att)); ?>" class="text-teal-600 hover:underline flex-1"><?php echo e($att->original_name); ?></a>
                <span class="text-gray-400 text-xs"><?php echo e($att->humanSize()); ?></span>
            </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
    <?php endif; ?>

    
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-4 text-sm uppercase tracking-wide">Approval History</h2>
        <?php if($pr->approvalActions->isEmpty()): ?>
            <p class="text-sm text-gray-400">Not yet submitted for approval.</p>
        <?php else: ?>
        <ol class="relative border-l-2 border-gray-100 space-y-5 ml-3">
            <?php $__currentLoopData = $pr->approvalActions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $dot = [
                    'approved' => 'bg-green-400',
                    'verified' => 'bg-teal-400',
                    'rejected' => 'bg-red-400',
                ][$action->decision] ?? 'bg-blue-400';
            ?>
            <li class="ml-5">
                <span class="absolute -left-1.5 mt-1 w-3 h-3 rounded-full <?php echo e($dot); ?> ring-2 ring-white"></span>
                <p class="text-xs text-gray-400">
                    <?php echo e($action->acted_at ? \Carbon\Carbon::parse($action->acted_at)->format('d M Y H:i') : ''); ?>

                    · <?php echo e($action->layerLabel()); ?>

                </p>
                <p class="text-sm font-semibold text-gray-800">
                    <?php echo e($action->actor?->name); ?>

                    <span class="font-normal text-gray-600">— <?php echo e(ucfirst($action->decision)); ?></span>
                </p>
                <?php if($action->note): ?>
                <p class="text-xs text-gray-500 mt-0.5 italic">"<?php echo e($action->note); ?>"</p>
                <?php endif; ?>
            </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ol>
        <?php endif; ?>
    </div>

    
    <?php if($pr->rfqs && $pr->rfqs->isNotEmpty()): ?>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-3 text-sm uppercase tracking-wide">Linked RFQs</h2>
        <div class="space-y-2">
            <?php $__currentLoopData = $pr->rfqs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rfq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                <span class="font-mono text-xs font-bold text-gray-700"><?php echo e($rfq->rfq_number ?? "RFQ #{$rfq->id}"); ?></span>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500"><?php echo e($rfq->quotes->count()); ?> quote(s)</span>
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.show', $rfq)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View →</a>
<?php endif; ?>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>

    
    <div class="flex flex-wrap gap-3 items-center">

        
        <?php if($pr->status === 'draft' && auth()->id() === $pr->creator_id): ?>
        <a href="<?php echo e(route('purchase-requests.edit', $pr)); ?>" class="btn-secondary">Edit Request</a>
        <form method="POST" action="<?php echo e(route('purchase-requests.submit', $pr)); ?>">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-primary">Submit for Approval</button>
        </form>
        <?php endif; ?>

        
        <?php if(app(\App\Services\ApprovalService::class)->canVerify($pr, auth()->user())): ?>
        <button type="button"
            onclick="document.getElementById('verify-modal').classList.remove('hidden')"
            class="px-5 py-2 rounded-lg text-sm font-semibold text-white"
            style="background:#1C3557;">
            Verify / Decision
        </button>
        <?php endif; ?>

        
        <?php if(app(\App\Services\ApprovalService::class)->canApprove($pr, auth()->user())): ?>
        <button type="button"
            onclick="document.getElementById('approve-modal').classList.remove('hidden')"
            class="px-5 py-2 rounded-lg text-sm font-semibold text-white bg-green-600 hover:bg-green-700">
            Approve / Reject
        </button>
        <?php endif; ?>

        
        <?php if($pr->status === 'approved' && auth()->user()->hasAnyRole(['finance','super_admin'])): ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.create')): ?>
<a href="<?php echo e(route('rfq.create', ['purchase_request_id' => $pr->id])); ?>"
           class="px-5 py-2 rounded-lg text-sm font-semibold text-white"
           style="background:#7C3AED;">
            + Create RFQ
        </a>
<?php endif; ?>
        <?php endif; ?>

        <a href="<?php echo e(route('purchase-requests.index')); ?>" class="text-gray-500 hover:text-gray-700 text-sm py-2 ml-auto">← Back to List</a>
    </div>
</div>


<div id="verify-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-base">Verify Purchase Request</h3>
            <button type="button" onclick="document.getElementById('verify-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?php echo e(route('purchase-requests.verify', $pr)); ?>">
            <?php echo csrf_field(); ?>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-teal-200 bg-teal-50 hover:bg-teal-100">
                        <input type="radio" name="decision" value="approved" required class="text-teal-500">
                        <span class="text-sm font-medium text-teal-700">✓ Verify</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100">
                        <input type="radio" name="decision" value="rejected" class="text-red-500">
                        <span class="text-sm font-medium text-red-700">✗ Reject</span>
                    </label>
                </div>
                <textarea name="note" rows="3" placeholder="Remarks (required for rejection)…"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-400 outline-none resize-none"></textarea>
                <div class="flex gap-3 pt-1">
                    <button type="submit" class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white" style="background:#1C3557;">Submit</button>
                    <button type="button" onclick="document.getElementById('verify-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>


<div id="approve-modal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-gray-900 text-base">Final Approval — Purchase Request</h3>
            <button type="button" onclick="document.getElementById('approve-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?php echo e(route('purchase-requests.approve', $pr)); ?>">
            <?php echo csrf_field(); ?>
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-green-200 bg-green-50 hover:bg-green-100">
                        <input type="radio" name="decision" value="approved" required class="text-green-500">
                        <span class="text-sm font-medium text-green-700">✓ Approve</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer px-3 py-2 rounded-lg border border-red-200 bg-red-50 hover:bg-red-100">
                        <input type="radio" name="decision" value="rejected" class="text-red-500">
                        <span class="text-sm font-medium text-red-700">✗ Reject</span>
                    </label>
                </div>
                <textarea name="note" rows="3" placeholder="Remarks (required for rejection)…"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none resize-none"></textarea>
                <div class="flex gap-3 pt-1">
                    <button type="submit" class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 rounded-lg text-sm font-semibold text-white">Submit Decision</button>
                    <button type="button" onclick="document.getElementById('approve-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/purchase-requests/show.blade.php ENDPATH**/ ?>