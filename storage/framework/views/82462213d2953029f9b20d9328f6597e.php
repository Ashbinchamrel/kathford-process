<?php if(isset($workingFiscalYear)): ?>
<span class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-semibold text-gray-600">Fiscal year <?php echo e($workingFiscalYear->name); ?><?php echo e($fiscalYearWritable ? ' · Active' : ' · Read-only'); ?></span>
<?php endif; ?>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/partials/fiscal-year-selector.blade.php ENDPATH**/ ?>