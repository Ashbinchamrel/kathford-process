@extends('layouts.app')
@section('title', 'Edit ' . $form->form_number)
@section('page-title', 'Edit ' . $form->form_number)

@section('content')
@php
    $lineItemsJson = json_encode($form->lineItems->map(function($i) {
        return [
            'id'          => $i->id,
            'description' => $i->item_name,
            'quantity'    => $i->quantity,
            'unit'        => $i->unit ?? '',
            'rate'        => $i->rate,
            'item_remarks' => $i->item_remarks,
            'amount'      => (float)$i->quantity * (float)$i->rate,
        ];
    })->values()->all());
    if (old('line_items') !== null) $lineItemsJson=json_encode(collect(old('line_items'))->map(fn($i)=>['description'=>$i['item_name']??'', 'quantity'=>$i['quantity']??1, 'unit'=>$i['unit']??'', 'rate'=>$i['rate']??0, 'item_remarks'=>$i['item_remarks']??'', 'amount'=>(float)($i['quantity']??0)*(float)($i['rate']??0)])->values()->all());
    $showReason    = $form->category?->requires_reason    ? 'true' : 'false';
    $showLogistics = $form->category?->requires_logistic_table ? 'true' : 'false';
@endphp
<div class="max-w-4xl" x-data="formBuilder({{ $lineItemsJson }}, {{ $showReason }}, {{ $showLogistics }}, @js($budgetOptions), '{{ old('budget_id', $form->budget_id) }}')">

    @can('activity_forms.edit')
<form method="POST" action="{{ route('activity-forms.update', $form) }}" enctype="multipart/form-data">
        @csrf
<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}"> @method('PUT')

        @if($errors->any())<div class="rounded-xl bg-red-50 text-red-800 p-4 mb-4 text-sm">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        {{-- Section 1 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <h2 class="text-base font-semibold text-gray-800 mb-5 pb-3 border-b border-gray-100">Section 1 – Form Details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select name="category_id" x-model="categoryId" @change="showReason=categories[categoryId].requires_reason; showLogistics=categories[categoryId].requires_logistic_table" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department_id" x-model="departmentId" @change="budgetId=''" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title / Subject <span class="text-red-500">*</span></label>
                    <input type="hidden" name="activity_name" :value="selectedBudgetTitle">
                    <label for="budget-search" class="sr-only">Search Title / Subject</label>
                    <input id="budget-search" type="search" x-model="budgetSearch" placeholder="Search activity title or fiscal year…" class="w-full mb-2 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <p x-show="filteredBudgets.length === 0" class="text-sm text-gray-500 mb-2" role="status">No matching budget activities.</p>
                    <select name="budget_id" x-model="budgetId" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">Select an allocated budget activity…</option>
                        <template x-for="budget in filteredBudgets" :key="budget.id">
                            <option :value="budget.id" x-text="`${budget.title} — FY ${budget.fiscal_year} (Remaining: ${formatNPR(budget.remaining)})`"></option>
                        </template>
                    </select>
                    <div class="mt-2 rounded-lg bg-teal-50 border border-teal-100 px-3 py-2 text-xs text-teal-800" x-show="selectedBudget">
                        Allocation: <span class="font-semibold" x-text="formatNPR(selectedBudget?.allocated)"></span>
                        <span class="mx-1">·</span> Current commitments: <span class="font-semibold" x-text="formatNPR(selectedBudget?.reserved)"></span>
                        <span class="mx-1">·</span> Remaining balance: <span class="font-semibold" x-text="formatNPR(selectedBudget?.remaining)"></span>
                    </div>
                    <div class="mt-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800" x-show="isOverBudget">
                        Budget variance: this request is <span class="font-semibold" x-text="formatNPR(budgetVariance)"></span> above the current remaining balance. This is informational only and will not change the approval process.
                    </div>
                    @error('budget_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Deadline Date <span class="text-red-500">*</span></label>
                    <input type="date" name="deadline_date" value="{{ old('deadline_date', $form->deadline_date?->format('Y-m-d')) }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div x-show="showReason">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason <span class="text-red-500">*</span></label>
                    <textarea name="unplanned_reason" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none">{{ old('unplanned_reason', $form->unplanned_reason) }}</textarea>
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="remarks" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none">{{ old('remarks', $form->remarks) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Section 2 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4" x-show="showLogistics">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">Section 2 – Logistics / Budget</h2>
                <button type="button" @click="addItem()" class="flex items-center gap-1 text-teal-600 hover:text-teal-700 text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Row
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead><tr class="bg-gray-50">
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 w-8">#</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500">Description</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 w-28">Qty</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 w-24">Unit</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 w-32">Rate</th>
                        <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500 w-32">Amount</th>
                        <th class="w-8"></th>
                    </tr></thead>
                    <tbody>
                        <template x-for="(item, idx) in items" :key="item.id">
                            <tr class="border-t border-gray-100">
                                <td class="px-3 py-2 text-gray-400" x-text="idx+1"></td>
                                <td class="px-3 py-2"><input type="text" :name="`line_items[${idx}][item_name]`" x-model="item.description" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-teal-400 outline-none" required><input :name="`line_items[${idx}][item_remarks]`" x-model="item.item_remarks" placeholder="Item remarks / specification" class="mt-2 w-full border border-gray-300 rounded px-2 py-1.5 text-xs"></td>
                                <td class="px-3 py-2"><input type="number" :name="`line_items[${idx}][quantity]`" x-model.number="item.quantity" @input="calcAmount(item)" step="0.01" min="0.01" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-teal-400 outline-none" required></td>
                                <td class="px-3 py-2"><input type="text" :name="`line_items[${idx}][unit]`" x-model="item.unit" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-teal-400 outline-none"></td>
                                <td class="px-3 py-2"><input type="number" :name="`line_items[${idx}][rate]`" x-model.number="item.rate" @input="calcAmount(item)" step="0.01" min="0" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-teal-400 outline-none" required></td>
                                <td class="px-3 py-2 text-right font-medium text-gray-700" x-text="'NPR '+parseFloat(item.amount||0).toLocaleString('en-NP',{minimumFractionDigits:2})"></td>
                                <td class="px-2 py-2"><button type="button" @click="removeItem(idx)" x-show="items.length>1" class="text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot><tr class="border-t-2 border-gray-200 bg-gray-50">
                        <td colspan="5" class="px-3 py-2 text-right text-sm font-semibold text-gray-700">Total</td>
                        <td class="px-3 py-2 text-right font-bold text-gray-900" x-text="'NPR '+total.toLocaleString('en-NP',{minimumFractionDigits:2})"></td>
                        <td></td>
                    </tr></tfoot>
                </table>
            </div>
        </div>

        {{-- Section 3: Attachments --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <h2 class="text-base font-semibold text-gray-800 mb-5 pb-3 border-b border-gray-100">Section 3 – Attachments</h2>
            <div class="space-y-3">
                @if($form->attachments->isNotEmpty())
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Existing Attachments</p>
                    <ul class="space-y-1">
                        @foreach($form->attachments as $att)
                        <li class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            {{ $att->original_name }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload New Files</label>
                    <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
                    <p class="text-xs text-gray-400 mt-1">PDF, Word, Excel, Images (max 10MB each)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Or Link to External Document</label>
                    <input type="url" name="external_link" value="{{ old('external_link') }}"
                           placeholder="https://drive.google.com/..."
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex gap-3">
            <button type="submit" name="action" value="draft" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">Save Draft</button>
            @can('activity_forms.submit')
<button type="submit" name="action" value="submit" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold transition-colors">Save &amp; Submit</button>
@endcan
            @can('activity_forms.view')
<a href="{{ route('activity-forms.show', $form) }}" class="text-gray-500 hover:text-gray-700 text-sm ml-auto py-2.5">Cancel</a>
@endcan
        </div>
    </form>
@endcan
</div>
@endsection

@push('scripts')
<script>
function formBuilder(initialItems, showReason, showLogistics, budgets, initialBudgetId) {
    return {
        init() { const category=this.categories[this.categoryId]; if(category) { this.showReason=category.requires_reason; this.showLogistics=category.requires_logistic_table; } },
        items: initialItems.length ? initialItems.map((i,idx) => ({...i, id: idx+1})) : [{ id:1, description:'', quantity:1, unit:'', rate:0, amount:0 }],
        nextId: initialItems.length + 1,
        showReason, showLogistics, budgets, budgetId: initialBudgetId,
        get total() { return this.items.reduce((s,i) => s+(i.amount||0), 0); },
        categoryId: @js(old('category_id', $form->category_id)),
        departmentId: @js(old('department_id', $form->department_id)),
        categories: @js($categories->keyBy('id')),
        budgetSearch: '',
        get filteredBudgets() { return this.budgets.filter(b => b.department_id === this.departmentId).filter(b => b.id === this.budgetId || `${b.title} ${b.fiscal_year}`.toLowerCase().includes(this.budgetSearch.trim().toLowerCase())); },
        get selectedBudget() { return this.budgets.find(b => b.id === this.budgetId) || null; },
        get selectedBudgetTitle() { return this.selectedBudget?.title || ''; },
        get isOverBudget() { return !!this.selectedBudget && this.total > Number(this.selectedBudget.remaining || 0) + 0.009; },
        get budgetVariance() { return this.isOverBudget ? this.total - Number(this.selectedBudget.remaining || 0) : 0; },
        calcAmount(item) { item.amount = (parseFloat(item.quantity)||0) * (parseFloat(item.rate)||0); },
        addItem() { this.items.push({ id: this.nextId++, description:'', quantity:1, unit:'', rate:0, amount:0 }); },
        removeItem(idx) { this.items.splice(idx, 1); },
        formatNPR(value) { return 'NPR ' + (parseFloat(value) || 0).toLocaleString('en-NP', { minimumFractionDigits: 2 }); },
    }
}
</script>
@endpush
