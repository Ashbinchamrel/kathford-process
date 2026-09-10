<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Submitted – Kathford International College</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { colors: { brand: '#0B1E3D', teal: { DEFAULT:'#00A99D' } } } } }</script>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="max-w-2xl mx-auto px-4 py-10">
    
    <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 bg-brand rounded-xl flex items-center justify-center text-white font-bold text-xl">K</div>
        <div>
            <h1 class="font-bold text-brand text-xl">Kathford International College</h1>
            <p class="text-gray-400 text-sm">Vendor Quotation Portal</p>
        </div>
    </div>

    
    <?php if(session('success')): ?>
        <div class="bg-teal/10 border border-teal text-teal-800 rounded-xl px-5 py-4 mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 shrink-0 text-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <p class="text-sm font-medium"><?php echo e(session('success')); ?></p>
        </div>
    <?php endif; ?>

    
    <div class="bg-white rounded-2xl border border-gray-200 p-8 text-center shadow-sm">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-800 mb-2">Quotation Submitted</h2>
        <p class="text-gray-500 text-sm mb-6">
            Your quotation for <span class="font-semibold text-gray-700"><?php echo e($quote->rfq->rfq_number); ?></span>
            has been received. The purchasing team will review it and be in touch.
        </p>

        <div class="bg-gray-50 rounded-xl p-4 text-left text-sm space-y-2 mb-4">
            <div class="flex justify-between">
                <span class="text-gray-500">Vendor</span>
                <span class="font-medium text-gray-800"><?php echo e($quote->vendor?->name); ?></span>
            </div>
            <?php if($quote->total_quoted !== null): ?>
            <div class="flex justify-between">
                <span class="text-gray-500">Total Amount</span>
                <span class="font-semibold text-gray-800">NPR <?php echo e(number_format($quote->total_quoted, 2)); ?></span>
            </div>
            <div class="flex justify-between"><span class="text-gray-500">Tax Amount</span><span class="font-semibold text-gray-800">NPR <?php echo e(number_format($quote->tax_amount, 2)); ?></span></div>
            <div class="flex justify-between border-t border-gray-200 pt-2"><span class="font-semibold text-gray-700">Grand Total</span><span class="font-bold text-gray-800">NPR <?php echo e(number_format($quote->grand_total, 2)); ?></span></div>
            <?php endif; ?>
            <?php if($quote->submitted_at): ?>
            <div class="flex justify-between">
                <span class="text-gray-500">Submitted At</span>
                <span class="text-gray-700"><?php echo e($quote->submitted_at->format('d M Y, g:i A')); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <p class="text-xs text-gray-400">This link has been used and is no longer active.</p>
    </div>
</div>
</body>
</html>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/rfq/vendor-submitted.blade.php ENDPATH**/ ?>