@extends('layouts.app')
@section('title', 'Edit PR')
@section('page-title', 'Edit PR: ' . $pr->pr_number)

@section('content')
<div class="max-w-4xl" x-data="prBuilder()">
    <form method="POST" action="{{ route('purchase-requests.update', $pr) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title', $pr->title) }}" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Activity Form</label>
                    <select name="activity_form_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                        <option value="">— Not linked —</option>
                        @foreach($activityForms as $af)
                            <option value="{{ $af->id }}" {{ old('activity_form_id', $pr->activity_form_id) == $af->id ? 'selected' : '' }}>{{ $af->form_number }} – {{ $af->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Required By</label>
                    <input type="date" name="required_by" value="{{ old('required_by', $pr->required_by?->format('Y-m-d')) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Justification</label>
                    <textarea name="justification" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none">{{ old('justification', $pr->justification) }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Items</h2>
                <button type="button" @click="addItem()" class="text-teal-600 hover:text-teal-700 text-sm font-medium">+ Add Item</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase border-b border-gray-100">
                            <th class="pb-2 pr-3">Description</th>
                            <th class="pb-2 pr-3 w-24">Unit</th>
                            <th class="pb-2 pr-3 w-24">Qty</th>
                            <th class="pb-2 pr-3 w-32">Est. Rate</th>
                            <th class="pb-2 pr-3 w-36">Amount</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, i) in items" :key="i">
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-3"><input type="text" :name="`items[${i}][description]`" x-model="item.description" required class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 outline-none"></td>
                                <td class="py-2 pr-3"><input type="text" :name="`items[${i}][unit]`" x-model="item.unit" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 outline-none"></td>
                                <td class="py-2 pr-3"><input type="number" :name="`items[${i}][quantity]`" x-model.number="item.quantity" min="0.01" step="0.01" required @input="calc(i)" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 outline-none"></td>
                                <td class="py-2 pr-3"><input type="number" :name="`items[${i}][unit_rate]`" x-model.number="item.unit_rate" min="0" step="0.01" @input="calc(i)" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 outline-none"></td>
                                <td class="py-2 pr-3 font-mono text-gray-600 text-right" x-text="item.amount ? 'Rs '+item.amount.toLocaleString('en-IN',{minimumFractionDigits:2}) : '—'"></td>
                                <td class="py-2"><button type="button" @click="removeItem(i)" x-show="items.length > 1" class="text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot><tr><td colspan="4" class="pt-3 text-right text-sm font-semibold text-gray-600 pr-3">Estimated Total:</td><td class="pt-3 font-mono font-bold text-gray-800" x-text="'Rs '+total.toLocaleString('en-IN',{minimumFractionDigits:2})"></td><td></td></tr></tfoot>
                </table>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" name="action" value="draft" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold">Save Draft</button>
            <button type="submit" name="action" value="submit" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold">Submit</button>
            <a href="{{ route('purchase-requests.show', $pr) }}" class="text-gray-500 hover:text-gray-700 text-sm py-2.5">Cancel</a>
        </div>
    </form>
</div>

@push('scripts')
<script>
function prBuilder() {
    const initial = {{ Illuminate\Support\Js::from($pr->lineItems->map(fn($i) => ['description'=>$i->description,'unit'=>$i->unit,'quantity'=>$i->quantity,'unit_rate'=>$i->unit_rate,'amount'=>$i->amount])) }};
    return {
        items: initial.length ? initial : [{ description:'',unit:'pcs',quantity:1,unit_rate:0,amount:0 }],
        get total() { return this.items.reduce((s,i) => s+(i.amount||0),0); },
        addItem() { this.items.push({description:'',unit:'pcs',quantity:1,unit_rate:0,amount:0}); },
        removeItem(i) { this.items.splice(i,1); },
        calc(i) { this.items[i].amount=(this.items[i].quantity||0)*(this.items[i].unit_rate||0); }
    };
}
</script>
@endpush
@endsection
