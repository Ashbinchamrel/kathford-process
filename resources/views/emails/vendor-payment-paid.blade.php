<p>Dear {{ $payment->vendor->name }},</p>

<p>We have completed the payment below.</p>

<ul>
    <li><strong>Paid amount: Rs {{ number_format($payment->amount_paid ?: $payment->net_amount ?: $payment->amount_due, 2) }}</strong></li>
    <li>Payment date: {{ $payment->actual_date?->format('d M Y') ?: '—' }}</li>
    <li>Transaction reference: {{ $payment->payment_reference ?: '—' }}</li>
    <li>Payment reference: {{ $payment->payment_number }}</li>
    <li>Bill number: {{ $payment->bill_number ?: '—' }}</li>
    <li>Purchase order: {{ $payment->purchaseOrder?->po_number ?: '—' }}</li>
</ul>

<p>Your Vendor Portal payment updates and account statement now reflect this settlement.</p>

<p>Regards,<br>Kathford International College</p>
