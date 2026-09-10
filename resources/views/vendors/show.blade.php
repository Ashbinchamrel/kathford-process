@extends('layouts.app')
@section('title', $vendor->name)
@section('page-title', $vendor->name)

@section('content')
<div class="max-w-3xl space-y-5">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $vendor->name }}</h2>
                <p class="text-gray-500 text-sm">{{ $vendor->category }} · {{ $vendor->company_type }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $vendor->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                </span>
                @if(auth()->user()->hasAnyRole(['super_admin','finance']))
                @can('vendors.view')
<a href="{{ route('vendors.statement', $vendor) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Account Statement</a>
@endcan
                @can('vendors.edit')
<a href="{{ route('vendors.edit', $vendor) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Edit</a>
@endcan
                @endif
            </div>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">PAN / VAT</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->pan_vat_number ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Owner</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->owner_name ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Contact Person</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->contact_person }}</dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Mobile</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->mobile_number }}</dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Office</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->office_number ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Email</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->email ?: '—' }}</dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-gray-400 uppercase tracking-wide">Address</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->address }}</dd></div>
            @if($vendor->notes)
            <div class="sm:col-span-2"><dt class="text-xs text-gray-400 uppercase tracking-wide">Notes</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->notes }}</dd></div>
            @endif
        </dl>
    </div>

    @if($showBankDetails)
    <div class="bg-white rounded-xl border border-amber-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Bank Details <span class="text-xs text-amber-600 font-normal">(Confidential – Finance &amp; Admin only)</span></h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Bank Name</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->bank_name ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Account Name</dt><dd class="text-sm text-gray-800 mt-1">{{ $vendor->bank_account_name ?: '—' }}</dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Account Number</dt><dd class="text-sm font-mono text-gray-800 mt-1">{{ $vendor->bank_account_number ?: '—' }}</dd></div>
        </dl>
    </div>
    @endif

    @if(auth()->user()->hasAnyRole(['super_admin','finance']))
    @can('vendors.edit')
<form method="POST" action="{{ route('vendors.portal-access', $vendor) }}" class="bg-white rounded-xl border border-gray-200 p-6">
        @csrf
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="font-semibold text-gray-800">Vendor Portal Access</h3>
                <p class="mt-1 text-sm text-gray-500">Set the approved email and initial password. The vendor can change their password after signing in.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $vendor->portal_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">{{ $vendor->portal_enabled ? 'Enabled' : 'Disabled' }}</span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Approved login email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $vendor->email) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                @error('email')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ $vendor->portal_password ? 'Reset password (optional)' : 'Set initial password' }}</label>
                <input type="password" name="portal_password" minlength="10" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                @error('portal_password')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                <input type="password" name="portal_password_confirmation" minlength="10" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div class="sm:col-span-2 flex items-center justify-between gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="portal_enabled" value="1" {{ old('portal_enabled', $vendor->portal_enabled) ? 'checked' : '' }} class="rounded text-teal-500">
                    <span class="text-sm font-medium text-gray-700">Enable vendor portal login</span>
                </label>
                <button class="px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold">Save portal access</button>
            </div>
        </div>
    </form>
@endcan
    @endif

    <div class="flex items-center gap-3">
        @can('vendors.view')
<a href="{{ route('vendors.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← Back to Vendors</a>
@endcan
        @if(auth()->user()->hasAnyRole(['super_admin','finance']))
        @can('vendors.delete')
<form method="POST" action="{{ route('vendors.destroy', $vendor) }}" class="ml-auto"
              onsubmit="return confirm('Are you sure?')">
            @csrf @method('DELETE')
            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                {{ $vendor->payments()->exists() || $vendor->rfqQuotes()->exists() ? 'Deactivate' : 'Delete' }}
            </button>
        </form>
@endcan
        @endif
    </div>
</div>
@endsection
