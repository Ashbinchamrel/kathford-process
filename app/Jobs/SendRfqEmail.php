<?php

namespace App\Jobs;

use App\Models\Rfq;
use App\Models\RfqQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRfqEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public RfqQuote $quote, public Rfq $rfq) {}

    public function handle(): void
    {
        $vendor = $this->quote->vendor;
        if (! $vendor || ! $vendor->email) return;

        $portalUrl = route('rfq.vendor.portal', $this->quote->vendor_token);

        Mail::send('emails.rfq-invitation', [
            'quote'     => $this->quote,
            'rfq'       => $this->rfq,
            'vendor'    => $vendor,
            'portalUrl' => $portalUrl,
        ], function ($message) use ($vendor) {
            $message->to($vendor->email, $vendor->name)
                    ->subject('[Kathford] Request for Quotation – ' . $this->rfq->rfq_number);
        });
    }
}
