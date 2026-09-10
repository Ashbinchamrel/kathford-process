@extends('layouts.app')
@section('title', 'Form Categories')
@section('page-title', 'Form Categories')

@section('content')
<div class="space-y-6">
    {{-- Existing categories --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Code</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Name</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Group</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Approval Chain</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Flags</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                        <th class="relative px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($categories as $cat)
                    <tr class="hover:bg-gray-50 {{ $cat->trashed() ? 'opacity-50' : '' }}">
                        <td class="px-5 py-3 font-mono font-bold text-gray-700">{{ $cat->code }}</td>
                        <td class="px-5 py-3 font-medium text-gray-900">
                            {{ $cat->name }}
                            @if($cat->is_system) <span class="ml-1 px-1.5 py-0.5 bg-gray-100 text-gray-500 text-xs rounded">System</span> @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 capitalize">{{ $cat->category_group }}</td>
                        <td class="px-5 py-3 text-gray-500 text-xs">{{ $cat->approvalChain?->name ?? 'Default' }}</td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                @if($cat->requires_reason) <span class="px-1.5 py-0.5 bg-orange-50 text-orange-600 text-xs rounded">Reason</span> @endif
                                @if($cat->requires_logistic_table) <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 text-xs rounded">Logistics</span> @endif
                                @if($cat->requires_attachments) <span class="px-1.5 py-0.5 bg-purple-50 text-purple-600 text-xs rounded">Attachments</span> @endif
                                @if($cat->bypasses_procurement_to_payment)<span class="px-1.5 py-0.5 bg-amber-50 text-amber-700 text-xs rounded">Bypass Procurement</span>@endif
                                @if($cat->requires_vendor_selection) <span class="px-1.5 py-0.5 bg-teal-50 text-teal-600 text-xs rounded">Vendor</span> @endif
                                
                            </div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $cat->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $cat->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-3">
                            @if(!$cat->trashed())
                                <a href="{{ route('admin.form-categories.edit', $cat) }}" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Edit</a>
                                @if(!$cat->is_system)
                                <form method="POST" action="{{ route('admin.form-categories.destroy', $cat) }}" class="inline" onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">Delete</button>
                                </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-5 py-12 text-center text-gray-400">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create new form --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="font-semibold text-gray-800 mb-5 pb-3 border-b border-gray-100">Add New Category</h2>
        <form method="POST" action="{{ route('admin.form-categories.store') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Code <span class="text-red-500">*</span> <span class="font-normal text-gray-400">(unique, uppercase)</span></label>
                    <input type="text" name="code" value="{{ old('code') }}" required maxlength="20" style="text-transform:uppercase"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:ring-2 focus:ring-teal-500 outline-none">
                    @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Group <span class="text-red-500">*</span></label>
                    <select name="category_group" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Select…</option>
                        <option value="activity" {{ old('category_group') === 'activity' ? 'selected' : '' }}>Activity</option>
                        <option value="purchase" {{ old('category_group') === 'purchase' ? 'selected' : '' }}>Purchase</option>
                        <option value="vendor" {{ old('category_group') === 'vendor' ? 'selected' : '' }}>Vendor</option>
                        <option value="payment" {{ old('category_group') === 'payment' ? 'selected' : '' }}>Payment</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Approval Chain</label>
                    <select name="approval_chain_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Use Default Chain</option>
                        @foreach($chains as $chain)
                            <option value="{{ $chain->id }}" {{ old('approval_chain_id') == $chain->id ? 'selected' : '' }}>{{ $chain->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 100) }}" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <p class="text-sm font-medium text-gray-700 mb-2">Form Features</p>
                    <div class="flex flex-wrap gap-4">
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="requires_reason" value="1" {{ old('requires_reason') ? 'checked' : '' }} class="rounded text-teal-500">
                            Requires Reason
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="requires_logistic_table" value="1" {{ old('requires_logistic_table', '1') ? 'checked' : '' }} class="rounded text-teal-500">
                            Logistics Table
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="requires_attachments" value="1" {{ old('requires_attachments', '1') ? 'checked' : '' }} class="rounded text-teal-500">
                            Attachments
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="requires_vendor_selection" value="1" {{ old('requires_vendor_selection') ? 'checked' : '' }} class="rounded text-teal-500">
                            Vendor Selection
                        </label>
                        <label class="flex items-center gap-2 text-sm"><input type="hidden" name="bypasses_procurement_to_payment" value="0"><input type="checkbox" name="bypasses_procurement_to_payment" value="1" {{ old('bypasses_procurement_to_payment') ? 'checked' : '' }} class="rounded text-teal-500"> Bypass Procurement</label>
                        <p class="text-sm text-gray-500">When checked, fully approved activities go directly to Payment Schedule for Accounts, skipping RFQs, Purchase Orders and Procurement Checklists. Otherwise, they go to RFQ preparation.</p>
                    </div>
                </div>
            </div>
            @if($errors->any())
                <div class="mt-4 bg-red-50 border border-red-200 rounded-lg px-4 py-3 text-sm text-red-700">
                    @foreach($errors->all() as $e) <p>{{ $e }}</p> @endforeach
                </div>
            @endif
            <div class="mt-5">
                <button type="submit" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold transition-colors">Add Category</button>
            </div>
        </form>
    </div>
</div>
@endsection
