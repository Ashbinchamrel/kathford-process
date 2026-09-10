<p>Dear <?php echo e($payment->vendor->name); ?>,</p>
<p>Kathford has recorded your pending payment schedule.</p>
<ul>
    <li>Gross payable amount: Rs <?php echo e(number_format($payment->amount_due, 2)); ?></li>
    <?php if($payment->tds_applied): ?><li>TDS deduction (<?php echo e($payment->tds_rate); ?>%): Rs <?php echo e(number_format($payment->tds_amount, 2)); ?></li><?php endif; ?>
    <li><strong>Net payable amount: Rs <?php echo e(number_format($payment->net_amount ?: $payment->amount_due, 2)); ?></strong></li>
    <li>Payment reference: <?php echo e($payment->payment_number); ?></li>
</ul>
<p>Scheduled payment period(s):</p>
<ul>
    <?php $__currentLoopData = $payment->schedules->sortBy('scheduled_date'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $schedule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($schedule->schedule_month?->format('F Y')); ?> · Week <?php echo e($schedule->schedule_week); ?>: Rs <?php echo e(number_format($schedule->net_amount ?: $schedule->amount_due, 2)); ?></li>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</ul>
<p>This is a planned payment schedule. Kathford will notify you again after payment authorisation.</p>
<p>Regards,<br>Kathford International College</p>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/emails/vendor-payment-scheduled.blade.php ENDPATH**/ ?>