<p>Dear <?php echo e($payment->vendor->name); ?>,</p>
<p>Your payment has been authorised by Kathford for processing.</p>
<ul>
    <li>Gross scheduled amount: Rs <?php echo e(number_format($payment->amount_due, 2)); ?></li>
    <?php if($payment->tds_applied): ?><li>TDS deduction (<?php echo e($payment->tds_rate); ?>%): Rs <?php echo e(number_format($payment->tds_amount, 2)); ?></li><?php endif; ?>
    <li><strong>Net payable amount: Rs <?php echo e(number_format($payment->net_amount ?: $payment->amount_due, 2)); ?></strong></li>
    <li>Payment reference: <?php echo e($payment->payment_number); ?></li>
    <li>Bill number: <?php echo e($payment->bill_number ?: '—'); ?></li>
    <li>Activity / reference: <?php echo e($payment->activity_name ?: $payment->activity_reference ?: '—'); ?></li>
    <li>Scheduled period: <?php echo e($payment->schedule_month?->format('F Y')); ?> · Week <?php echo e($payment->schedule_week); ?></li>
</ul>
<p>The payment will be completed according to the scheduled banking process. Please contact Kathford if you need clarification.</p>
<p>Regards,<br>Kathford International College</p>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/emails/vendor-payment-authorised.blade.php ENDPATH**/ ?>