<p>Dear <?php echo e($payment->vendor->name); ?>,</p>

<p>We have completed the payment below.</p>

<ul>
    <li><strong>Paid amount: Rs <?php echo e(number_format($payment->amount_paid ?: $payment->net_amount ?: $payment->amount_due, 2)); ?></strong></li>
    <li>Payment date: <?php echo e($payment->actual_date?->format('d M Y') ?: '—'); ?></li>
    <li>Transaction reference: <?php echo e($payment->payment_reference ?: '—'); ?></li>
    <li>Payment reference: <?php echo e($payment->payment_number); ?></li>
    <li>Bill number: <?php echo e($payment->bill_number ?: '—'); ?></li>
    <li>Purchase order: <?php echo e($payment->purchaseOrder?->po_number ?: '—'); ?></li>
</ul>

<p>Your Vendor Portal payment updates and account statement now reflect this settlement.</p>

<p>Regards,<br>Kathford International College</p>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/emails/vendor-payment-paid.blade.php ENDPATH**/ ?>