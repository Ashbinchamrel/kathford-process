@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')
@section('content')
<div class="max-w-6xl">
    <p class="text-sm text-gray-500 mt-1 mb-5">Choose what you want to manage.</p>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @can('procurement_setup.manage')
        <a href="{{ route('admin.procurement.index') }}" class="kcard p-5 block hover:bg-teal-50">
            <h2 class="font-semibold text-teal-700">Procurement Setup →</h2>
            <p class="text-sm text-gray-500 mt-2">Approved vendor rates, Excel uploads, RFQ assignment and checklist questions.</p>
        </a>
        @endcan
        @if(auth()->user()->isSuperAdmin())
        @foreach([
            ['admin.users.index','Users','User accounts, roles and permissions.'],
            ['admin.departments.index','Departments','Manage your organisation’s departments.'],
            ['admin.approval-chain.index','Approval Chains','Set verification and approval steps.'],
            ['admin.form-categories.index','Form Categories','Manage the types of activity forms.'],
            ['admin.payees.index','Payee Information','Payees and payment accounts.'],
            ['admin.profile.edit','Organisation Profile','Organisation details and fiscal years.'],
            ['admin.audit-logs.index','Audit Logs','Review recorded system activity.'],
        ] as [$route,$title,$description])
        <a href="{{ route($route) }}" class="kcard p-5 block hover:bg-teal-50">
            <h2 class="font-semibold text-teal-700">{{ $title }} →</h2>
            <p class="text-sm text-gray-500 mt-2">{{ $description }}</p>
        </a>
        @endforeach
        @endif
    </div>
</div>
@endsection
