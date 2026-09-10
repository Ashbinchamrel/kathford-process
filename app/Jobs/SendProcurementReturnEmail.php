<?php
namespace App\Jobs;
use App\Models\ProcurementReturn;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
class SendProcurementReturnEmail implements ShouldQueue {
    use Queueable;
    public function __construct(public ProcurementReturn $return) {}
    public function handle(): void {
        $vendor=$this->return->vendor;
        if (!$vendor?->email) return;
        $bill=$this->return->checklist->vendorBill;
        $text="Invoice {$bill->bill_number} has been returned.\n\n".$this->return->reason."\n\nPlease review your supplier portal and submit the corrected goods/invoice as requested. Use a revised invoice reference to preserve the original invoice history.";
        Mail::raw($text,fn($message)=>$message->to($vendor->email)->subject('Invoice returned — '.$bill->bill_number));
    }
}
