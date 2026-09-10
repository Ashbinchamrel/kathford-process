<?php

namespace App\Jobs;

use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendPurchaseOrderEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public PurchaseOrder $purchaseOrder) {}

    public function handle(): void
    {
        $po     = $this->purchaseOrder->load(['vendor', 'rfqQuote.rfq', 'items', 'generatedBy']);
        $vendor = $po->vendor;

        if (! $vendor || ! $vendor->email) return;

        $pdf = Pdf::loadView('purchase-orders.pdf', compact('po'))->output();

        Mail::send('emails.purchase-order', [
            'po' => $po,
            'vendor' => $vendor,
            'portalUrl' => route('vendor.portal.dashboard'),
        ], function ($message) use ($po, $vendor, $pdf) {
            $message->to($vendor->email, $vendor->name)
                    ->subject('[Kathford] Purchase Order – ' . $po->po_number)
                    ->attachData($pdf, "PO-{$po->po_number}.pdf", ['mime' => 'application/pdf']);
        });
    }
}
