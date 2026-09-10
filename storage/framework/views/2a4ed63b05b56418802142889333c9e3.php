<?php $__env->startSection('title', 'Compare Quotes'); ?>
<?php $__env->startSection('page-title', 'Quote Comparison: ' . $rfq->rfq_number); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-5">
    <?php if(session('success')): ?><div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800"><?php echo e(session('success')); ?></div><?php endif; ?>
    <?php if($errors->any()): ?><div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800"><?php echo e($errors->first()); ?></div><?php endif; ?>

    <div class="rounded-xl border border-teal-200 bg-teal-50 px-5 py-4 text-sm text-teal-900">
        <p class="font-semibold">Quotation comparison for <?php echo e($rfq->activityForm?->form_number ?? $rfq->rfq_number); ?></p>
        <p class="mt-1 text-teal-800">The lowest unit price is highlighted as a recommendation. Award each item to the vendor you choose, or send an item-specific negotiation request before making an award.</p>
    </div>

    <?php if($quotes->isEmpty()): ?>
    <div class="rounded-xl border border-gray-200 bg-white px-6 py-10 text-center text-sm text-gray-500">No vendor quotations have been submitted yet.</div>
    <?php else: ?>
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="sticky left-0 z-10 bg-gray-50 px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">Item</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase text-gray-500">Unit</th>
                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase text-gray-500">Qty</th>
                    <?php $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <th class="min-w-56 px-5 py-3 text-center text-xs font-semibold uppercase <?php echo e($quote->id === $recommendedQuoteId ? 'bg-green-100 text-green-800' : 'text-gray-500'); ?>">
                        <?php echo e($quote->vendor?->name); ?>

                        <?php if($quote->id === $recommendedQuoteId): ?><span class="mt-1 block text-[10px] normal-case">Lowest total for all requested items</span><?php endif; ?>
                    </th>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemKey => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr class="align-top hover:bg-gray-50">
                    <td class="sticky left-0 z-10 bg-white px-5 py-4 font-medium text-gray-800"><?php echo e($item->description); ?>

                        <?php if($approvedSuggestions[$itemKey]->isNotEmpty()): ?>
                        <div class="mt-3 rounded-lg border border-teal-100 bg-teal-50 p-3 text-xs font-normal text-teal-900">
                            <p class="font-semibold">Lowest current approved rate</p>
                            <?php $__currentLoopData = $approvedSuggestions[$itemKey]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $suggestion): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="mt-2">
                                <p class="font-semibold"><?php echo e($suggestion->vendor->name); ?> · Rs <?php echo e(number_format($suggestion->unit_rate, 2)); ?>/<?php echo e($suggestion->unit); ?></p>
                                <p class="mt-1">Valid until <?php echo e($suggestion->valid_until->format('d M Y')); ?></p>
                                <?php if($suggestion->specification): ?><p class="mt-1"><?php echo e($suggestion->specification); ?></p><?php endif; ?>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <p class="mt-2">Matched by item name. Compare units and specifications before choosing a vendor.</p>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-5 py-4 text-center text-gray-500"><?php echo e($item->unit); ?></td>
                    <td class="px-5 py-4 text-center font-mono"><?php echo e($item->quantity); ?></td>
                    <?php $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $qi = $quoteItems[$quote->id][$itemKey] ?? null; ?>
                    <td class="px-5 py-3 text-center <?php echo e($qi && (float) $qi->unit_rate === (float) ($minRates[$itemKey] ?? null) ? 'bg-green-50' : ''); ?>">
                        <?php if($qi): ?>
                            <p class="font-mono font-semibold <?php echo e((float) $qi->unit_rate === (float) ($minRates[$itemKey] ?? null) ? 'text-green-700' : 'text-gray-800'); ?>">Rs <?php echo e(number_format($qi->unit_rate, 2)); ?></p>
                            <p class="mt-1 text-xs text-gray-500">Line total: Rs <?php echo e(number_format($qi->total, 2)); ?></p>
                            <?php if($qi->award_status === 'accepted'): ?>
                                <span class="mt-3 inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800">✓ Awarded to this vendor</span>
                            <?php elseif($qi->award_status === 'rejected'): ?>
                                <span class="mt-3 inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Not selected</span>
                            <?php elseif($quote->status === 'submitted'): ?>
                                <div class="mt-3 flex flex-wrap justify-center gap-2">
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.accept_quote')): ?>
<form method="POST" action="<?php echo e(route('rfq.accept-quote-item', [$rfq, $quote, $qi])); ?>" onsubmit="return confirm('Award <?php echo e(addslashes($qi->description)); ?> to <?php echo e(addslashes($quote->vendor?->name)); ?>? This will replace any earlier award for this item.')">
                                        <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
                                        <button class="rounded-lg bg-green-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-green-600">Award</button>
                                    </form>
<?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.accept_quote')): ?>
                                    <?php if($quote->entry_method !== 'catalogue'): ?>
                                    <button type="button" onclick="openNegotiationModal('<?php echo e(route('rfq.request-negotiation', [$rfq, $quote, $qi])); ?>', '<?php echo e(addslashes($qi->description)); ?>', '<?php echo e(addslashes($quote->vendor?->name)); ?>')" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100">Negotiate</button>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.accept_quote')): ?>
<form method="POST" action="<?php echo e(route('rfq.reject-quote-item', [$rfq, $quote, $qi])); ?>" onsubmit="return confirm('Reject <?php echo e(addslashes($qi->description)); ?> from <?php echo e(addslashes($quote->vendor?->name)); ?>?')">
                                        <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
                                        <button class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100">Reject</button>
                                    </form>
<?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if($qi->negotiation_message): ?>
                                <p class="mt-3 rounded-md bg-amber-50 px-2 py-1.5 text-left text-xs leading-5 text-amber-800">Negotiation sent <?php echo e($qi->negotiation_requested_at?->format('d M Y')); ?>.</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-gray-400">—</span>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
            <tfoot class="bg-gray-50 font-semibold">
                <tr>
                    <td colspan="3" class="px-5 py-3 text-right text-gray-600">Total amount:</td>
                    <?php $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <td class="px-5 py-3 text-center font-mono <?php echo e($quote->id === $recommendedQuoteId ? 'bg-green-100 text-green-800' : 'text-gray-700'); ?>">Rs <?php echo e(number_format($quote->total_quoted, 2)); ?></td>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
                <tr>
                    <td colspan="3" class="px-5 py-3 text-right text-gray-600">Tax amount:</td>
                    <?php $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><td class="px-5 py-3 text-center font-mono text-gray-700">Rs <?php echo e(number_format($quote->tax_amount, 2)); ?></td><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
                <tr class="bg-teal-50">
                    <td colspan="3" class="px-5 py-3 text-right font-bold text-teal-900">Grand total:</td>
                    <?php $__currentLoopData = $quotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quote): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><td class="px-5 py-3 text-center font-mono font-bold <?php echo e($quote->id === $recommendedQuoteId ? 'text-green-800' : 'text-teal-900'); ?>">Rs <?php echo e(number_format($quote->grand_total, 2)); ?></td><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-600">
        <p class="font-semibold text-gray-800">How item awards work</p>
        <p class="mt-1">You can award different items to different vendors. Awarding an item automatically removes an earlier award for the same item, so you can correct or revise your choice before creating purchase orders.</p>
    </div>
    <?php endif; ?>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.show', $rfq)); ?>" class="text-sm text-gray-500 hover:text-gray-700">← Back to RFQ</a>
<?php endif; ?>
</div>

<div id="negotiation-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
    <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
        <div class="flex items-start justify-between gap-4"><div><h2 class="font-bold text-gray-900">Request negotiation</h2><p id="negotiation-target" class="mt-1 text-sm text-gray-500"></p></div><button type="button" onclick="closeNegotiationModal()" class="text-xl text-gray-400 hover:text-gray-700">×</button></div>
        <form id="negotiation-form" method="POST" class="mt-5 space-y-4">
            <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
            <div><label class="mb-1 block text-sm font-medium text-gray-700">Message to vendor <span class="text-red-500">*</span></label><textarea name="message" rows="5" required placeholder="Explain the requested revision, target price, specification clarification, or terms to negotiate." class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500"></textarea></div>
            <div class="rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">The vendor will receive an email and see this request against the selected item in their portal.</div>
            <div class="flex justify-end gap-3"><button type="button" onclick="closeNegotiationModal()" class="px-4 py-2 text-sm text-gray-600">Cancel</button><button class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600">Send negotiation request</button></div>
        </form>
    </div>
</div>

<script>
function openNegotiationModal(action, item, vendor) {
    document.getElementById('negotiation-form').action = action;
    document.getElementById('negotiation-target').textContent = `${item} — ${vendor}`;
    document.getElementById('negotiation-modal').classList.remove('hidden');
    document.getElementById('negotiation-modal').classList.add('flex');
}
function closeNegotiationModal() {
    document.getElementById('negotiation-modal').classList.add('hidden');
    document.getElementById('negotiation-modal').classList.remove('flex');
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/rfq/compare.blade.php ENDPATH**/ ?>