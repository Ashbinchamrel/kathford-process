<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendVendorPaymentPaidEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Payment $payment) {}

    public function handle(): void
    {
        $payment = $this->payment->load(['vendor', 'purchaseOrder']);

        if (! $payment->vendor?->email) {
            return;
        }

        Mail::send('emails.vendor-payment-paid', compact('payment'), function ($message) use ($payment) {
            $message->to($payment->vendor->email, $payment->vendor->name)
                ->subject('[Kathford] Payment completed – '.$payment->payment_number);
        });
    }
}
