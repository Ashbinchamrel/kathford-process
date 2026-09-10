<?php if(isset($fiscalYearWritable) && !$fiscalYearWritable): ?>
<div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900" role="status">
    <?php if($workingFiscalYear): ?>
        Viewing <?php echo e($workingFiscalYear->name); ?> · Read-only. Select the active year to create or change transactions.
        <?php if($workingFiscalYear->is_legacy): ?> These older entries had no reliable budget-year link and have been preserved here. <?php endif; ?>
    <?php else: ?>
        No active fiscal year is configured. Set up a fiscal year under Organisation Profile before creating transactions.
    <?php endif; ?>
</div>
<?php endif; ?>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/partials/fiscal-year-notice.blade.php ENDPATH**/ ?>