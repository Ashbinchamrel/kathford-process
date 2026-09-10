<p>Dear {{ $payment->vendor->name }},</p>
<p>Kathford has recorded your pending payment schedule.</p>
<ul>
    <li>Gross payable amount: Rs {{ number_format($payment->amount_due, 2) }}</li>
    @if($payment->tds_applied)<li>TDS deduction ({{ $payment->tds_rate }}%): Rs {{ number_format($payment->tds_amount, 2) }}</li>@endif
    <li><strong>Net payable amount: Rs {{ number_format($payment->net_amount ?: $payment->amount_due, 2) }}</strong></li>
    <li>Payment reference: {{ $payment->payment_number }}</li>
</ul>
<p>Scheduled payment period(s):</p>
<ul>
    @foreach($payment->schedules->sortBy('scheduled_date') as $schedule)
        <li>{{ $schedule->schedule_month?->format('F Y') }} · Week {{ $schedule->schedule_week }}: Rs {{ number_format($schedule->net_amount ?: $schedule->amount_due, 2) }}</li>
    @endforeach
</ul>
<p>This is a planned payment schedule. Kathford will notify you again after payment authorisation.</p>
<p>Regards,<br>Kathford International College</p>
