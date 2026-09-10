<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityForm;
use App\Models\AuditLog;
use App\Models\GoodsReceivedNote;
use App\Models\Payment;
use App\Models\PaymentAuthorisation;
use App\Models\ProcurementChecklist;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Rfq;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The operational record lists use this endpoint for a Super Admin-only,
 * reversible clean-up action. It deliberately refuses to erase financial or
 * control records that have progressed beyond their safe correction point.
 */
class TransactionBulkDeleteController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const MODELS = [
        'activity_forms' => ActivityForm::class,
        'purchase_requests' => PurchaseRequest::class,
        'rfqs' => Rfq::class,
        'purchase_orders' => PurchaseOrder::class,
        'checklists' => ProcurementChecklist::class,
        'payments' => Payment::class,
        'payment_authorisations' => PaymentAuthorisation::class,
    ];

    public function destroy(Request $request, string $module): RedirectResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403);
        abort_unless(isset(self::MODELS[$module]), 404);

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);

        $model = self::MODELS[$module];
        $records = $model::whereIn('id', array_unique($data['ids']))->get();
        if ($records->isEmpty()) {
            throw ValidationException::withMessages([
                'ids' => 'None of the selected records is currently available. Reload the list and try again.',
            ]);
        }

        DB::transaction(function () use ($records, $module) {
            foreach ($records as $record) {
                $this->ensureSafeToDelete($record);
            }

            foreach ($records as $record) {
                $this->softDelete($record);
                AuditLog::record(
                    Auth::user(),
                    'transaction.bulk_deleted',
                    $record,
                    "{$module}: {$this->reference($record)} removed by Super Admin"
                );
            }
        });

        return back()->with('success', $records->count().' selected record'.($records->count() === 1 ? ' was' : 's were').' removed. This action is retained in the audit log.');
    }

    private function ensureSafeToDelete(Model $record): void
    {
        if ($record instanceof PurchaseOrder) {
            $this->rejectIf(
                $record->vendorBills()->exists() || $record->goodsReceived()->exists() || $record->payments()->exists() || $record->procurementChecklists()->exists(),
                'A Purchase Order with receipt, invoice, checklist, or payment records cannot be removed.'
            );
        }

        if ($record instanceof GoodsReceivedNote) {
            $this->rejectIf($record->isConfirmed(), 'A confirmed GRN cannot be removed. Record a corrective transaction instead.');
        }

        if ($record instanceof ProcurementChecklist) {
            $this->rejectIf($record->isSentToAccounts(), 'A checklist already sent to Accounts cannot be removed.');
        }

        if ($record instanceof Payment) {
            $schedules = $record->schedules()->get();
            $this->rejectIf($record->status === 'paid' || $schedules->contains('status', 'paid'), 'A paid payment cannot be removed.');
        }

        if ($record instanceof PaymentAuthorisation) {
            // A Super Admin can undo an approved batch during testing or a
            // controlled correction. Payments already marked paid remain an
            // immutable financial fact and must be corrected separately.
            $this->rejectIf(
                $record->payments()->where('status', 'paid')->exists(),
                'A Payment Authorisation containing a paid payment cannot be removed.'
            );
        }
    }

    private function rejectIf(bool $condition, string $message): void
    {
        if ($condition) {
            throw ValidationException::withMessages(['ids' => $message]);
        }
    }

    private function softDelete(Model $record): void
    {
        if ($record instanceof PaymentAuthorisation) {
            // Returning an unapproved batch to the schedule queue prevents its
            // child rows from being stranded if an authorisation was created in error.
            $record->payments()->where('status', '!=', 'paid')->update([
                'payment_authorisation_id' => null,
                'status' => 'scheduled',
            ]);
        }

        if ($record instanceof Payment) {
            $record->schedules()->delete();
        }

        $record->delete();
    }

    private function reference(Model $record): string
    {
        foreach (['form_number', 'rfq_number', 'po_number', 'grn_number', 'payment_number', 'authorisation_number', 'title', 'bill_number'] as $field) {
            if (filled($record->getAttribute($field))) {
                return (string) $record->getAttribute($field);
            }
        }

        return (string) $record->getKey();
    }
}
