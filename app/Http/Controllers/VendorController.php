<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Vendor;
use App\Support\VendorStatement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class VendorController extends Controller
{
    private function authorizeVendorWrite(): void
    {
        abort_unless(
            auth()->user()->isSuperAdmin()
                || auth()->user()->permissions()->where('module', 'vendors')->exists(),
            403,
            'You do not have vendor-management access.'
        );
    }

    public function index(Request $request): View
    {
        $query = Vendor::withTrashed()->latest();
        \App\Support\RecordVisibility::apply($query, Auth::user());

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('contact_person', 'like', "%{$request->search}%")
                  ->orWhere('pan_vat_number', 'like', "%{$request->search}%");
            });
        }
        if ($request->category) $query->where('category', $request->category);
        if ($request->has('is_active') && $request->is_active !== '') {
            $query->where('is_active', (bool) $request->is_active);
        }

        $vendors    = $query->paginate(25)->withQueryString();
        $categories = Vendor::categories();

        return view('vendors.index', compact('vendors', 'categories'));
    }

    public function create(): View
    {
        $this->authorizeVendorWrite();
        return view('vendors.create', [
            'categories'   => Vendor::categories(),
            'companyTypes' => Vendor::companyTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeVendorWrite();
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'is_active'          => ['boolean'],
            'category'           => ['required', 'string'],
            'pan_vat_number'     => ['nullable', 'string', 'max:20'],
            'company_type'       => ['nullable', 'string'],
            'owner_name'         => ['nullable', 'string', 'max:100'],
            'contact_person'     => ['required', 'string', 'max:100'],
            'mobile_number'      => ['required', 'string', 'max:20'],
            'office_number'      => ['nullable', 'string', 'max:20'],
            'address'            => ['required', 'string'],
            'email'              => ['nullable', 'email', 'max:255'],
            'bank_name'          => ['nullable', 'string', 'max:100'],
            'bank_account_name'  => ['nullable', 'string', 'max:100'],
            'bank_account_number'=> ['nullable', 'string', 'max:50'],
            'notes'              => ['nullable', 'string'],
        ]);

        $vendor = Vendor::create(array_merge($validated, [
            'created_by' => Auth::id(),
            'is_active'  => $request->boolean('is_active', true),
        ]));

        AuditLog::record(Auth::user(), 'vendor.created', $vendor, $vendor->name);

        return redirect()->route('vendors.show', $vendor)
            ->with('success', "Vendor \"{$vendor->name}\" added to the registry.");
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load('createdBy');
        $showBankDetails = Auth::user()->can('vendors.edit');

        return view('vendors.show', compact('vendor', 'showBankDetails'));
    }

    public function statement(Vendor $vendor): View
    {
        $this->authorizeVendorWrite();
        return view('vendors.statement', array_merge(compact('vendor'), VendorStatement::for($vendor)));
    }

    public function edit(Vendor $vendor): View
    {
        $this->authorizeVendorWrite();
        return view('vendors.edit', [
            'vendor'       => $vendor,
            'categories'   => Vendor::categories(),
            'companyTypes' => Vendor::companyTypes(),
        ]);
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeVendorWrite();
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'is_active'          => ['boolean'],
            'category'           => ['required', 'string'],
            'pan_vat_number'     => ['nullable', 'string', 'max:20'],
            'company_type'       => ['nullable', 'string'],
            'owner_name'         => ['nullable', 'string', 'max:100'],
            'contact_person'     => ['required', 'string', 'max:100'],
            'mobile_number'      => ['required', 'string', 'max:20'],
            'office_number'      => ['nullable', 'string', 'max:20'],
            'address'            => ['required', 'string'],
            'email'              => ['nullable', 'email', 'max:255'],
            'bank_name'          => ['nullable', 'string', 'max:100'],
            'bank_account_name'  => ['nullable', 'string', 'max:100'],
            'bank_account_number'=> ['nullable', 'string', 'max:50'],
            'notes'              => ['nullable', 'string'],
        ]);

        $vendor->update(array_merge($validated, [
            'updated_by' => Auth::id(),
            'is_active'  => $request->boolean('is_active', true),
        ]));

        AuditLog::record(Auth::user(), 'vendor.updated', $vendor, $vendor->name);

        return redirect()->route('vendors.show', $vendor)
            ->with('success', 'Vendor updated.');
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $this->authorizeVendorWrite();
        if ($vendor->payments()->exists() || $vendor->rfqQuotes()->exists()) {
            // Soft delete (deactivate) rather than hard delete
            $vendor->update(['is_active' => false]);
            AuditLog::record(Auth::user(), 'vendor.deactivated', $vendor, $vendor->name);
            return redirect()->route('vendors.index')
                ->with('success', "Vendor deactivated (has transaction history).");
        }

        AuditLog::record(Auth::user(), 'vendor.deleted', $vendor, $vendor->name);
        $vendor->delete();

        return redirect()->route('vendors.index')
            ->with('success', 'Vendor removed from registry.');
    }

    public function configurePortal(Request $request, Vendor $vendor): RedirectResponse
    {
        $this->authorizeVendorWrite();

        $data = $request->validate([
            'email'          => ['required', 'email', 'max:255', 'unique:vendors,email,' . $vendor->id],
            'portal_enabled' => ['boolean'],
            'portal_password'=> ['nullable', 'string', 'min:10', 'confirmed'],
        ]);

        $enablePortal = $request->boolean('portal_enabled');
        if ($enablePortal && ! $vendor->portal_password && empty($data['portal_password'])) {
            return back()->withErrors([
                'portal_password' => 'Set an initial password before enabling this vendor portal account.',
            ]);
        }

        $updates = [
            'email'          => strtolower($data['email']),
            'portal_enabled' => $enablePortal,
            'updated_by'     => Auth::id(),
        ];
        if (! empty($data['portal_password'])) {
            $updates['portal_password'] = Hash::make($data['portal_password']);
            $updates['portal_password_changed_at'] = now();
        }

        $vendor->update($updates);
        AuditLog::record(Auth::user(), 'vendor.portal_access_updated', $vendor, $vendor->name);

        return redirect()->route('vendors.show', $vendor)
            ->with('success', 'Vendor portal access updated.');
    }
}
