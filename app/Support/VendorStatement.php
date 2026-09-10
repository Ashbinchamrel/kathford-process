<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\Vendor;
use Carbon\CarbonInterface;

/** Builds the same invoice-and-settlement ledger for Finance and the supplier. */
final class VendorStatement
{
    public static function for(Vendor $vendor): array
    {
        $bills = $vendor->bills()->with('purchaseOrder')->orderBy('bill_date')->get();
        $payments = $vendor->payments()
            ->with('purchaseOrder')
            ->where('status', 'paid')
            ->where(fn ($query) => $query->whereNotNull('parent_payment_id')->orWhereNotIn('source', ['checklist', 'external']))
            ->orderBy('actual_date')
            ->get();

        $entries = $bills->map(fn ($bill) => [
            'date' => $bill->bill_date,
            'type' => 'Invoice',
            'reference' => $bill->bill_number,
            'description' => $bill->purchaseOrder?->po_number ?? 'Vendor invoice',
            'debit' => (float) $bill->amount,
            'credit' => 0.0,
        ])->merge($payments->map(fn (Payment $payment) => [
            'date' => $payment->actual_date,
            'type' => 'Payment',
            'reference' => $payment->payment_reference ?: $payment->payment_number,
            'description' => $payment->purchaseOrder?->po_number ?? 'Payment settlement',
            'debit' => 0.0,
            'credit' => (float) ($payment->amount_paid ?: $payment->net_amount ?: $payment->amount_due),
        ]))->sortBy(fn (array $entry) => $entry['date'] instanceof CarbonInterface ? $entry['date']->format('Y-m-d') : '')->values();

        $balance = 0.0;
        $entries = $entries->map(function (array $entry) use (&$balance) {
            $balance += $entry['debit'] - $entry['credit'];
            $entry['balance'] = round($balance, 2);
            return $entry;
        });

        return [
            'entries' => $entries,
            'invoiced' => round($bills->sum('amount'), 2),
            'paid' => round($payments->sum(fn (Payment $payment) => (float) ($payment->amount_paid ?: $payment->net_amount ?: $payment->amount_due)), 2),
            'balance' => round($balance, 2),
        ];
    }
}
