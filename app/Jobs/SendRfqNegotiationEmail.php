<?php

namespace App\Jobs;

use App\Models\RfqQuoteItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRfqNegotiationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public RfqQuoteItem $quoteItem) {}

    public function handle(): void
    {
        $this->quoteItem->loadMissing('quote.vendor', 'quote.rfq');
        $quote = $this->quoteItem->quote;
        $vendor = $quote?->vendor;
        $rfq = $quote?->rfq;

        if (! $vendor?->email || ! $rfq) {
            return;
        }

        Mail::send('emails.rfq-negotiation', [
            'quoteItem' => $this->quoteItem,
            'quote' => $quote,
            'rfq' => $rfq,
            'vendor' => $vendor,
            'portalUrl' => route('vendor.portal.login'),
        ], function ($message) use ($vendor, $rfq) {
            $message->to($vendor->email, $vendor->name)
                ->subject('[Kathford] Negotiation request – ' . $rfq->rfq_number);
        });
    }
}
