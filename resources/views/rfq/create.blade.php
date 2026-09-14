@extends('layouts.app')
@section('title', 'Prepare RFQ')
@section('page-title', 'Prepare Request for Quotation')
@section('content')
<div class="max-w-6xl space-y-4" x-data="rfqBuilder()">
    @if($errors->any())<div class="rounded-xl bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ route('rfq.store') }}" class="space-y-4">
        @csrf<input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
        @if($preparationRfq)
            <input type="hidden" name="rfq_id" value="{{ $preparationRfq->id }}">
            <div class="kcard p-5"><h2 class="font-semibold">Approved Activity · {{ $selectedActivity->form_number }}</h2><p class="mt-1 text-sm text-gray-500">{{ $selectedActivity->activity_name }} · Complete sourcing for the approved items.</p></div>
        @endif
        <section class="kcard p-5 space-y-4">
            <h2 class="font-semibold">RFQ Details</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    @if($preparationRfq)
                    <label for="linked-activity-title" class="block text-sm font-medium mb-1">Title / Subject *</label>
                    <input id="linked-activity-title" readonly value="{{ $selectedActivity?->activity_name ?? $preparationRfq->title }}" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    <input type="hidden" name="budget_id" value="{{ $preparationRfq->budget_id ?? $selectedActivity?->budget_id }}">
                    <p class="mt-1 text-xs text-gray-500">Carried forward from the approved activity.</p>
                    @else
                    <label for="budget-search" class="block text-sm font-medium mb-1">Title / Subject *</label>
                    <input id="budget-search" type="search" x-model="budgetSearch" placeholder="Search budget activity or department" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-2">
                    <select name="budget_id" x-model="budgetId" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select a budget activity</option><template x-for="budget in filteredBudgets" :key="budget.id"><option :value="budget.id" x-text="budget.activity_title+' · '+(budget.department?.name || '')"></option></template></select>
                    @endif
                </div>
                <label class="text-sm font-medium">Quotation deadline<input type="date" name="deadline" value="{{ old('deadline') }}" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2"></label>
                <label class="text-sm font-medium sm:col-span-2">Instructions to vendors<textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2">{{ old('notes', $preparationRfq?->notes) }}</textarea></label>
            </div>
        </section>
        <section class="kcard p-5">
            <div class="mb-4 flex justify-between"><h2 class="font-semibold">Items and Vendors</h2><button type="button" @click="addItem()" class="text-sm font-semibold text-teal-700">+ Add item</button></div>
            <div class="space-y-4"><template x-for="(item,index) in items" :key="item.key">
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-3">
                    <input type="hidden" :name="`items[${index}][source_line_item_id]`" :value="item.source_line_item_id || ''">
                    <div class="grid gap-3 lg:grid-cols-[minmax(0,2fr)_90px_90px_minmax(0,2fr)_auto] items-start">
                        <label class="text-xs font-semibold text-gray-600">Description<input :name="`items[${index}][description]`" x-model="item.description" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></label>
                        <label class="text-xs font-semibold text-gray-600">Quantity<input type="number" min="0.01" step="0.01" :name="`items[${index}][quantity]`" x-model="item.quantity" required class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></label>
                        <label class="text-xs font-semibold text-gray-600">Unit<input :name="`items[${index}][unit]`" x-model="item.unit" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></label>
                        <div class="relative" @click.outside="item.open=false" x-show="!item.quotation_not_required && !item.available_in_store"><label class="text-xs font-semibold text-gray-600">Vendors to invite *</label>
                            <button type="button" @click="item.open=!item.open" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm" x-text="item.vendor_ids.length ? item.vendor_ids.length+' vendors selected' : 'Choose vendors'"></button>
                            <div x-show="item.open" x-cloak class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white p-3 shadow-lg"><input type="search" x-model="item.search" placeholder="Search vendors" class="w-full border rounded px-2 py-1 text-sm"><div class="max-h-48 overflow-auto"><template x-for="vendor in filteredVendors(item.search)" :key="vendor.id"><label class="flex gap-2 py-2 text-sm"><input type="checkbox" x-model="item.vendor_ids" :value="vendor.id" @change="item.vendor_rate_id=''"><span x-text="vendor.name"></span></label></template></div></div>
                            <template x-for="id in item.vendor_ids" :key="id"><input type="hidden" :name="`items[${index}][vendor_ids][]`" :value="id"></template>
                        </div>
                        <button type="button" @click="items.splice(index,1)" class="mt-5 text-xs text-red-600" x-show="items.length>1 && !item.source_line_item_id">Remove</button>
                    </div>
                    <label class="block text-xs font-semibold text-gray-600">Request remarks / specification<textarea :name="`items[${index}][request_remarks]`" x-model="item.request_remarks" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Specifications, brand, delivery requirements…"></textarea></label>
                    <div class="flex flex-wrap gap-3 items-center">
                        @if($preparationRfq)<label class="text-sm" x-show="item.source_line_item_id && !item.available_in_store"><input type="checkbox" :name="`items[${index}][quotation_not_required]`" value="1" x-model="item.quotation_not_required" @change="item.vendor_rate_id=''; if(item.quotation_not_required) item.available_in_store=false"> Quotation Not Required</label>
                        <label class="text-sm" x-show="item.source_line_item_id && !item.quotation_not_required"><input type="checkbox" :name="`items[${index}][available_in_store]`" value="1" x-model="item.available_in_store" @change="item.vendor_rate_id=''; item.vendor_ids=[]; if(item.available_in_store) item.quotation_not_required=false"> Available in Store</label>@endif
                        <select :name="`items[${index}][vendor_rate_id]`" x-model="item.vendor_rate_id" @change="applyRate(item)" x-show="!item.quotation_not_required && !item.available_in_store" class="max-w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Request vendor quotations</option><template x-for="rate in ratesFor(item)" :key="rate.id"><option :value="String(rate.id)" x-text="'Approved rate: '+rate.item_name+' · '+rate.vendor_name+' · Rs '+rate.unit_rate+'/'+rate.unit+' · until '+rate.valid_until"></option></template></select>
                        <p x-show="!item.quotation_not_required && !item.available_in_store && !item.vendor_ids.length" class="text-xs text-gray-500">Select a vendor to see their approved rates.</p>
                        <p x-show="!item.quotation_not_required && !item.available_in_store && item.vendor_ids.length && !ratesFor(item).length" class="text-xs text-gray-500">No current approved rates for the selected vendors. Request quotations to continue.</p>
                        <p x-show="item.quotation_not_required" class="text-xs text-amber-800">Finance receives the original approved activity quantity and amount.</p>
                        <p x-show="item.available_in_store" class="text-xs text-amber-800">No vendor is involved — this item is already in campus store and will appear on the Store Action list.</p>
                    </div>
                </div>
            </template></div>
        </section>
        <div class="flex gap-3"><button type="submit" class="btn-secondary">Save preparation</button>@can('rfq.send')<button name="send_now" value="1" type="submit" class="btn-primary">Save and send RFQ</button>@endcan<a href="{{ route('rfq.index') }}" class="btn-quiet">Cancel</a></div>
    </form>
</div>
<script>
function rfqBuilder() {
    const initial=@js($initialItems);
    return {
        budgets:@js($budgetOptions), rates:@js($catalogRates->map(fn($rate)=>array_merge($rate->only(['id','vendor_id','item_name','unit','unit_rate','specification']), ['vendor_name'=>$rate->vendor->name,'valid_until'=>$rate->valid_until->format('Y-m-d')]))), vendors:@js($vendorPayload), budgetSearch:'',budgetId:@js(old('budget_id',$preparationRfq?->budget_id ?? '')),
        items:initial.map((item,i)=>({...item,key:i,quotation_not_required:!!item.quotation_not_required,available_in_store:!!item.available_in_store,vendor_rate_id:item.vendor_rate_id||'',vendor_ids:(item.vendor_ids||[]).map(String),open:false,search:''})),
        nextKey:initial.length,
        init(){this.items.forEach(item=>{const rate=this.rates.find(r=>String(r.id)===String(item.vendor_rate_id));if(rate)item.vendor_ids=[String(rate.vendor_id)];});},
        ratesFor(item){return this.rates.filter(r=>item.vendor_ids.includes(String(r.vendor_id)));},
        get filteredBudgets(){return this.budgets.filter(b=>b.id===this.budgetId || `${b.activity_title} ${b.department?.name}`.toLowerCase().includes(this.budgetSearch.toLowerCase()));},
        filteredVendors(search){return this.vendors.filter(v=>`${v.name} ${v.email}`.toLowerCase().includes(search.toLowerCase()));},
        addItem(){this.items.push({key:this.nextKey++,description:'',quantity:1,unit:'pcs',request_remarks:'',vendor_ids:[],vendor_rate_id:'',quotation_not_required:false,available_in_store:false,open:false,search:''});},
        applyRate(item){const r=this.rates.find(r=>String(r.id)===String(item.vendor_rate_id));if(r){item.vendor_ids=[String(r.vendor_id)];item.description=r.item_name;item.unit=r.unit;if(!item.request_remarks)item.request_remarks=r.specification||'';item.quotation_not_required=false;}}
    };
}
</script>
@endsection
