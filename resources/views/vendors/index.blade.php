@extends('layouts.app')
@section('title', 'Vendors')
@section('page-title', 'Vendor Registry')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-3">
    @can('vendors.create')<div class="flex justify-end gap-3"><a href="{{ route('vendors.create') }}" class="btn-primary">+ Add vendor</a></div>@endcan
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm"><div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
        <form method="GET" class="flex gap-3 flex-1 flex-wrap items-end">
            <label class="block flex-1 min-w-[200px]"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">Find a vendor</span><div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="{{ request('search') }}" placeholder="Vendor name or contact" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-100"></div></label>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase text-gray-500">Category</span><select name="category" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select></label>
            <button class="btn-primary h-[42px]">Apply filters</button>
        </form>
    </div></section>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Category</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Contact</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Mobile</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vendors as $vendor)
                    <tr class="hover:bg-gray-50 {{ $vendor->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-3 py-2 font-medium text-gray-900">{{ $vendor->name }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $vendor->category }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $vendor->contact_person }}</td>
                        <td class="px-3 py-2 text-gray-500">{{ $vendor->mobile_number }}</td>
                        <td class="px-3 py-2">
                            @if($vendor->trashed())
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs">Deleted</span>
                            @elseif($vendor->is_active)
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">Active</span>
                            @else
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">Inactive</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right">
                            @can('vendors.view')
<a href="{{ route('vendors.show', $vendor) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">View →</a>
@endcan
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-gray-400">No vendors found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vendors->hasPages())
            <div class="px-3 py-2 border-t border-gray-100">{{ $vendors->withQueryString()->links() }}</div>
        @endif
    </div>
</div>
@endsection
