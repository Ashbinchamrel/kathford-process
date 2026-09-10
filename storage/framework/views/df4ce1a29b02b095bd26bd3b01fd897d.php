<?php $__env->startSection('title', $rfq->rfq_number); ?>
<?php $__env->startSection('page-title', 'RFQ: ' . $rfq->rfq_number); ?>

<?php $__env->startSection('content'); ?>
<?php if($rfq->handoff_activity_id && !$rfq->requestItems()->exists() && !$rfq->quotes()->exists()): ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.create')): ?><div class="kcard p-5 mb-4"><p class="text-sm mb-3">This approved activity is ready for RFQ preparation.</p><a href="<?php echo e(route('rfq.create',['rfq_id'=>$rfq->id])); ?>" class="btn-primary">Complete RFQ preparation</a></div><?php endif; ?>
<?php endif; ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.send')): ?>
<?php if($additionalVendors->isNotEmpty() && $rfq->quotes->isNotEmpty() && !in_array($rfq->status,['closed','cancelled'])): ?>
<details class="kcard p-5 mb-4"><summary class="font-semibold cursor-pointer">Request quotations from additional vendors</summary>
<form method="POST" action="<?php echo e(route('rfq.invite-additional',$rfq)); ?>" class="mt-4 space-y-3"><?php echo csrf_field(); ?><input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>"><p class="text-sm text-gray-500">Choose additional vendors to quote on these items. No amount is required.</p><select name="vendor_ids[]" multiple required class="w-full border rounded-lg p-3 text-sm"><?php $__currentLoopData = $additionalVendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($vendor->id); ?>"><?php echo e($vendor->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><button class="btn-primary">Send quotation requests</button></form></details>
<?php endif; ?>
<?php endif; ?>
<?php if($rfq->requestItems()->where('quotation_not_required',true)->exists()): ?>
<div class="kcard p-5 mb-4"><h2 class="font-semibold mb-3">Items sent directly to Payment Schedule</h2><?php $__currentLoopData = $rfq->requestItems()->where('quotation_not_required',true)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p class="text-sm"><?php echo e($item->description); ?> · Approved Activity <?php echo e($rfq->activityForm?->form_number); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
<?php endif; ?>
<div class="max-w-5xl space-y-5">
    
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex flex-wrap gap-6 justify-between">
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Activity Form</p>
                <?php if($rfq->activityForm): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.view')): ?>
<a href="<?php echo e(route('activity-forms.show', $rfq->activityForm)); ?>" class="text-teal-600 hover:underline font-medium"><?php echo e($rfq->activityForm->form_number); ?></a>
<?php endif; ?>
                <?php else: ?>
                <span class="text-gray-500">Standalone RFQ</span>
                <?php endif; ?>
                <p class="text-gray-700 text-sm mt-0.5"><?php echo e($rfq->activityForm?->activity_name); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Status</p>
                <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-700"><?php echo e($rfq->statusLabel()); ?></span>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Deadline</p>
                <p class="text-gray-700 font-medium"><?php echo e($rfq->deadline ? $rfq->deadline->format('d M Y') : '—'); ?></p>
            </div>
        </div>
        <?php if($rfq->notes): ?>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Scope / Instructions</p>
            <p class="text-sm text-gray-700"><?php echo e($rfq->notes); ?></p>
        </div>
        <?php endif; ?>
    </div>

    
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-semibold text-gray-800">Vendor Quotes (<?php echo e($rfq->quotes->count()); ?>)</h2>
            <div class="flex gap-2">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.pdf', $rfq)); ?>" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold">Download PDF</a>
<?php endif; ?>
                <?php if($rfq->status === 'draft'): ?>

                <?php if(in_array($rfq->status, ['draft', 'open'])): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.edit')): ?>
<a href="<?php echo e(route('rfq.edit', $rfq)); ?>" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold">Edit</a>
<?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.delete')): ?>
<form method="POST" action="<?php echo e(route('rfq.destroy', $rfq)); ?>" onsubmit="return confirm('Delete this RFQ?')" class="inline">
                    <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>"> <?php echo method_field('DELETE'); ?>
                    <button class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-lg text-sm font-semibold">Delete</button>
                </form>
<?php endif; ?>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.send')): ?>
<form method="POST" action="<?php echo e(route('rfq.send', $rfq)); ?>">
                    <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
                    <button class="px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold">Send Invitations</button>
                </form>
<?php endif; ?>
                <?php endif; ?>
                <?php if(in_array($rfq->status, ['sent', 'quotes_received', 'partially_awarded', 'items_awarded'])): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.compare', $rfq)); ?>" class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg text-sm font-semibold">Compare Quotes</a>
<?php endif; ?>
                <button onclick="document.getElementById('manual-quote-modal').classList.remove('hidden')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium">Enter Manual Quote</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="divide-y divide-gray-100">
            <?php $__empty_1 = true; $__currentLoopData = $rfq->quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
            $qs=['pending'=>'bg-gray-100 text-gray-500','submitted'=>'bg-blue-100 text-blue-700','accepted'=>'bg-green-100 text-green-700','rejected'=>'bg-red-100 text-red-700','manual'=>'bg-orange-100 text-orange-700'];
            ?>
            <div class="px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="font-medium text-gray-800"><?php echo e($quote->vendor?->name); ?></p>
                    <p class="text-xs text-gray-400"><?php echo e($quote->vendor?->email); ?></p>
                    <?php if($quote->submitted_at): ?>
                    <p class="text-xs text-gray-400 mt-0.5">Submitted <?php echo e($quote->submitted_at->format('d M Y H:i')); ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-4">
                    <?php if($quote->total_quoted !== null): ?>
                    <div class="text-right"><p class="font-mono font-bold text-gray-800">Rs <?php echo e(number_format($quote->grand_total, 2)); ?></p><p class="mt-0.5 text-xs text-gray-400">Total Rs <?php echo e(number_format($quote->total_quoted, 2)); ?> + Tax Rs <?php echo e(number_format($quote->tax_amount, 2)); ?></p></div>
                    <?php endif; ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo e($qs[$quote->status] ?? 'bg-gray-100 text-gray-500'); ?>"><?php echo e($quote->vendorStatusLabel()); ?></span>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="px-6 py-8 text-center text-gray-400 text-sm">No vendor quotes yet.</div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.index')); ?>" class="text-gray-500 hover:text-gray-700 text-sm">← Back to RFQs</a>
<?php endif; ?>
</div>


<div id="manual-quote-modal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
        <h3 class="font-bold text-gray-900 mb-4">Enter Manual Quote</h3>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.create')): ?>
<form method="POST" action="<?php echo e(route('rfq.manual-quote', $rfq)); ?>">
            <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor <span class="text-red-500">*</span></label>
                    <select name="vendor_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Select vendor…</option>
                        <?php $__currentLoopData = $rfq->quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $q): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($q->vendor_id); ?>"><?php echo e($q->vendor?->name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Total Amount (Rs) <span class="text-red-500">*</span></label>
                    <input id="manual-total-amount" type="number" name="total_amount" step="0.01" min="0" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tax Amount (Rs)</label>
                    <input id="manual-tax-amount" type="number" name="tax_amount" value="0" step="0.01" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="rounded-lg bg-teal-50 px-3 py-2 text-sm text-teal-900"><span class="font-medium">Grand Total:</span> <span id="manual-grand-total" class="font-mono font-bold">Rs 0.00</span></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none"></textarea>
                </div>
            </div>
            <div class="flex gap-3 mt-5">
                <button type="submit" class="flex-1 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold">Save Quote</button>
                <button type="button" onclick="document.getElementById('manual-quote-modal').classList.add('hidden')" class="px-4 py-2 text-gray-500 text-sm">Cancel</button>
            </div>
        </form>
<?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const total = document.getElementById('manual-total-amount');
    const tax = document.getElementById('manual-tax-amount');
    const grand = document.getElementById('manual-grand-total');
    const recalculate = () => grand.textContent = `Rs ${((Number.parseFloat(total.value) || 0) + (Number.parseFloat(tax.value) || 0)).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    total?.addEventListener('input', recalculate); tax?.addEventListener('input', recalculate); recalculate();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/rfq/show.blade.php ENDPATH**/ ?>