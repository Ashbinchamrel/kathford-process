<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Quotation – Kathford International College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { brand: '#0B1E3D', teal: { DEFAULT:'#00A99D' } } } } }</script>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="max-w-4xl mx-auto px-4 py-10">
    
    <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 bg-brand rounded-xl flex items-center justify-center text-white font-bold text-xl">K</div>
        <div>
            <h1 class="font-bold text-brand text-xl">Kathford International College</h1>
            <p class="text-gray-400 text-sm">Vendor Quotation Portal</p>
        </div>
    </div>

    <?php if($errors->any()): ?>
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800">
            <?php echo e($errors->first()); ?>

        </div>
    <?php endif; ?>

    
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">RFQ Reference</p>
                <p class="font-bold text-gray-800 font-mono"><?php echo e($rfq->rfq_number); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Vendor</p>
                <p class="font-medium text-gray-800"><?php echo e($quote->vendor?->name); ?></p>
            </div>
            <div>
                <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Submission Deadline</p>
                <p class="font-medium text-gray-800"><?php echo e($rfq->deadline ? $rfq->deadline->format('d M Y') : 'Open'); ?></p>
            </div>
        </div>
        <?php if($rfq->notes): ?>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-1">Scope / Instructions</p>
            <p class="text-sm text-gray-700"><?php echo e($rfq->notes); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <?php
        $awardedItems = $quote->items->where('award_status', 'accepted');
        $allItemsRejected = $quote->items->isNotEmpty() && $quote->items->every(fn ($item) => $item->award_status === 'rejected');
    ?>
    <?php if(($quote->status === 'accepted' || $awardedItems->isNotEmpty()) && !($allowEdit ?? false)): ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 text-center">
        <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="font-bold text-green-800 text-lg"><?php echo e($quote->status === 'accepted' ? 'Your quotation has been accepted!' : $awardedItems->count().' item'.($awardedItems->count() === 1 ? ' has' : 's have').' been awarded to your company!'); ?></p>
        <p class="text-green-600 text-sm mt-1"><?php echo e($awardedItems->pluck('description')->join(', ')); ?>. The issued Purchase Order is available from your dashboard.</p>
        </div>
    <?php elseif($allItemsRejected): ?>
    <div class="bg-slate-100 border border-slate-200 rounded-xl p-6 text-center"><p class="font-bold text-slate-800 text-lg">This quotation was not selected.</p><p class="text-slate-600 text-sm mt-1">Thank you for taking part in this request.</p></div>
    <?php elseif($quote->status === 'submitted' && ! ($allowEdit ?? false)): ?>
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
        <p class="font-bold text-blue-800">Quotation already submitted.</p>
        <p class="text-blue-600 text-sm mt-1">We are reviewing your quotation. Thank you!</p>
    </div>
    <?php else: ?>
    
    <form method="POST" action="<?php echo e($submitUrl ?? route('rfq.vendor-submit', $quote->vendor_token)); ?>">
        <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100 font-semibold text-gray-800">Items & Pricing</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Item Description</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Unit</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Qty Required</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Your Unit Rate (Rs)</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Total (Rs)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php $__currentLoopData = $quote->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="px-5 py-3 text-gray-400"><?php echo e($idx + 1); ?></td>
                            <td class="px-5 py-3 text-gray-800 font-medium">
                                <span class="block"><?php echo e($item->description); ?></span>
                                <?php if($item->request_remarks): ?>
                                    <span class="mt-1 block whitespace-pre-line text-xs font-normal leading-5 text-slate-500"><span class="font-semibold text-slate-600">Request details:</span> <?php echo e($item->request_remarks); ?></span>
                                <?php endif; ?>
                                <?php if($item->negotiation_message): ?>
                                    <span class="mt-2 block whitespace-pre-line rounded-md border border-amber-200 bg-amber-50 p-2 text-xs font-normal leading-5 text-amber-900"><span class="font-semibold">Negotiation request from Kathford:</span> <?php echo e($item->negotiation_message); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-gray-500"><?php echo e($item->unit); ?></td>
                            <td class="px-5 py-3 text-right font-mono"><?php echo e($item->quantity); ?></td>
                            <td class="px-5 py-3">
                                <input type="number" name="items[<?php echo e($item->id); ?>][unit_rate]" value="<?php echo e(old("items.{$item->id}.unit_rate", $item->unit_rate)); ?>" step="0.01" min="0" required <?php echo e(in_array($item->award_status, ['accepted', 'rejected'], true) ? 'readonly' : ''); ?>

                                       data-quote-rate data-quantity="<?php echo e($item->quantity); ?>" data-total-target="quote-line-total-<?php echo e($idx); ?>"
                                       placeholder="0.00"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm text-right font-mono focus:ring-2 focus:ring-teal-500 outline-none">
                            </td>
                            <td id="quote-line-total-<?php echo e($idx); ?>" class="px-5 py-3 text-right font-mono text-gray-700">Rs 0.00</td>
                        </tr>
                        <?php if($item->award_status === 'accepted'): ?>
                        <tr class="bg-green-50"><td colspan="6" class="px-5 py-2 text-right text-xs font-semibold text-green-800">✓ This item has been awarded to your company.</td></tr>
                        <?php elseif($item->award_status === 'rejected'): ?>
                        <tr class="bg-slate-50"><td colspan="6" class="px-5 py-2 text-right text-xs font-semibold text-slate-600">This item was not selected.</td></tr>
                        <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="5" class="px-5 py-3 text-right font-semibold text-gray-600">Total Amount:</td>
                            <td id="quote-total-amount" class="px-5 py-3 text-right font-mono font-bold text-gray-800">Rs 0.00</td>
                        </tr>
                        <tr>
                            <td colspan="5" class="px-5 py-3 text-right font-semibold text-gray-600"><label class="inline-flex items-center gap-2"><input type="hidden" name="tax_applied" value="0"><input type="checkbox" name="tax_applied" value="1" data-quote-tax-applied <?php if(old('tax_applied', $quote->tax_applied)): echo 'checked'; endif; ?>> Apply Tax / VAT</label></td>
                            <td class="px-5 py-2"><label class="text-xs text-gray-500">Rate % <input type="number" name="tax_rate" value="<?php echo e(old('tax_rate', $quote->tax_rate ?: 13)); ?>" step="0.01" min="0" max="100" data-quote-tax-rate class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-right font-mono text-sm"></label></td>
                        </tr>
                        <tr class="bg-teal-50">
                            <td colspan="5" class="px-5 py-3 text-right font-bold text-teal-900">Grand Total:</td>
                            <td id="quote-grand-total" class="px-5 py-3 text-right font-mono font-bold text-teal-900">Rs 0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Timeline</label>
                <input type="text" name="delivery_timeline" value="<?php echo e(old('delivery_timeline', $quote->delivery_timeline)); ?>" placeholder="e.g. 3-5 business days" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Terms</label>
                <input type="text" name="payment_terms" value="<?php echo e(old('payment_terms', $quote->payment_terms)); ?>" placeholder="e.g. 30 days net" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Remarks</label>
                <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none"><?php echo e(old('notes', $quote->notes)); ?></textarea>
            </div>
        </div>

        <button type="submit" class="w-full py-3 bg-teal-500 hover:bg-teal-600 text-white rounded-xl text-sm font-bold transition-colors">
            Submit Quotation
        </button>
        <p class="text-center text-xs text-gray-400 mt-3">By submitting, you confirm the prices are valid for 30 days.</p>
    </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const currency = new Intl.NumberFormat('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const rates = document.querySelectorAll('[data-quote-rate]');
    const totalAmount = document.getElementById('quote-total-amount');
    const taxApplied = document.querySelector('[data-quote-tax-applied]');
    const taxRate = document.querySelector('[data-quote-tax-rate]');
    const grandTotal = document.getElementById('quote-grand-total');

    const recalculate = () => {
        let grand = 0;
        rates.forEach((input) => {
            const rate = Number.parseFloat(input.value) || 0;
            const quantity = Number.parseFloat(input.dataset.quantity) || 0;
            const total = rate * quantity;
            grand += total;
            document.getElementById(input.dataset.totalTarget).textContent = `Rs ${currency.format(total)}`;
        });
        const taxAmount = taxApplied.checked ? grand * (Number.parseFloat(taxRate.value) || 0) / 100 : 0;
        totalAmount.textContent = `Rs ${currency.format(grand)}`;
        grandTotal.textContent = `Rs ${currency.format(grand + taxAmount)}`;
    };

    rates.forEach((input) => input.addEventListener('input', recalculate));
    taxApplied.addEventListener('change', recalculate);
    taxRate.addEventListener('input', recalculate);
    recalculate();
});
</script>
</body>
</html>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/rfq/vendor-portal.blade.php ENDPATH**/ ?>