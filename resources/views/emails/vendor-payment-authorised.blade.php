<p>Dear {{ $payment->vendor->name }},</p>
<p>Your payment has been authorised by Kathford for processing.</p>
<ul>
    <li>Gross scheduled amount: Rs {{ number_format($payment->amount_due, 2) }}</li>
    @if($payment->tds_applied)<li>TDS deduction ({{ $payment->tds_rate }}%): Rs {{ number_format($payment->tds_amount, 2) }}</li>@endif
    <li><strong>Net payable amount: Rs {{ number_format($payment->net_amount ?: $payment->amount_due, 2) }}</strong></li>
    <li>Payment reference: {{ $payment->payment_number }}</li>
    <li>Bill number: {{ $payment->bill_number ?: '—' }}</li>
    <li>Activity / reference: {{ $payment->activity_name ?: $payment->activity_reference ?: '—' }}</li>
    <li>Scheduled period: {{ $payment->schedule_month?->format('F Y') }} · Week {{ $payment->schedule_week }}</li>
</ul>
<p>The payment will be completed according to the scheduled banking process. Please contact Kathford if you need clarification.</p>
<p>Regards,<br>Kathford International College</p>
