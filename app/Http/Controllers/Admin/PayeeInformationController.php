<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payee;
use App\Models\PaymentAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PayeeInformationController extends Controller
{
    public function index(): View
    {
        return view('admin.payees.index', [
            'payees' => Payee::latest()->paginate(20, ['*'], 'payees'),
            'accounts' => PaymentAccount::orderBy('name')->get(),
            'types' => ['Student', 'Employee', 'Supplier', 'Consultant', 'Government', 'Other'],
        ]);
    }

    public function storePayee(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'custom_type' => ['nullable', 'string', 'max:100'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'account_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        if ($data['type'] === '__other__') {
            abort_unless(filled($data['custom_type'] ?? null), 422, 'Enter the new payee type.');
            $data['type'] = trim($data['custom_type']);
        }
        unset($data['custom_type']);
        $payee = Payee::create($data + ['active' => true]);
        AuditLog::record(Auth::user(), 'payee.created', $payee, $payee->name);
        return back()->with('success', 'Payee added.');
    }

    public function updatePayee(Request $request, Payee $payee): RedirectResponse
    {
        $data = $request->validate(['name' => ['required','string','max:255'], 'type' => ['required','string','max:100'], 'bank_name' => ['nullable','string','max:255'], 'account_number' => ['nullable','string','max:100'], 'account_name' => ['nullable','string','max:255'], 'notes' => ['nullable','string','max:2000'], 'active' => ['nullable','boolean']]);
        $payee->update($data + ['active' => $request->boolean('active')]);
        AuditLog::record(Auth::user(), 'payee.updated', $payee, $payee->name);
        return back()->with('success', 'Payee updated.');
    }

    public function destroyPayee(Payee $payee): RedirectResponse
    {
        abort_if($payee->payments()->exists(), 422, 'This payee is used by a payment and cannot be deleted. Deactivate it instead.');
        $payee->delete();
        return back()->with('success', 'Payee deleted.');
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required','string','max:255','unique:payment_accounts,name'], 'code' => ['nullable','string','max:100','unique:payment_accounts,code']]);
        PaymentAccount::create($data + ['active' => true]);
        return back()->with('success', 'Payment account added.');
    }

    public function updateAccount(Request $request, PaymentAccount $paymentAccount): RedirectResponse
    {
        $data = $request->validate(['name' => ['required','string','max:255','unique:payment_accounts,name,'.$paymentAccount->id], 'code' => ['nullable','string','max:100','unique:payment_accounts,code,'.$paymentAccount->id], 'active' => ['nullable','boolean']]);
        $paymentAccount->update($data + ['active' => $request->boolean('active')]);
        return back()->with('success', 'Payment account updated.');
    }

    public function destroyAccount(PaymentAccount $paymentAccount): RedirectResponse
    {
        abort_if($paymentAccount->payments()->exists(), 422, 'This account is used by a payment and cannot be deleted. Deactivate it instead.');
        $paymentAccount->delete();
        return back()->with('success', 'Payment account deleted.');
    }
}
