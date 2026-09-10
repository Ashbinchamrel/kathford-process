<?php $__env->startSection('title', 'Prepare RFQ'); ?>
<?php $__env->startSection('page-title', 'Prepare Request for Quotation'); ?>
<?php $__env->startSection('content'); ?>
<div class="max-w-6xl space-y-4" x-data="rfqBuilder()">
    <?php if($errors->any()): ?><div class="rounded-xl bg-red-50 p-4 text-sm text-red-800"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div><?php endif; ?>
    <form method="POST" action="<?php echo e(route('rfq.store')); ?>" class="space-y-4">
        <?php echo csrf_field(); ?><input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
        <?php if($preparationRfq): ?>
            <input type="hidden" name="rfq_id" value="<?php echo e($preparationRfq->id); ?>">
            <div class="kcard p-5"><h2 class="font-semibold">Approved Activity · <?php echo e($selectedActivity->form_number); ?></h2><p class="mt-1 text-sm text-gray-500"><?php echo e($selectedActivity->activity_name); ?> · Complete sourcing for the approved items.</p></div>
        <?php endif; ?>
        <section class="kcard p-5 space-y-4">
            <h2 class="font-semibold">RFQ Details</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <?php if($preparationRfq): ?>
                    <label for="linked-activity-title" class="block text-sm font-medium mb-1">Title / Subject *</label>
                    <input id="linked-activity-title" readonly value="<?php echo e($selectedActivity?->activity_name ?? $preparationRfq->title); ?>" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                    <input type="hidden" name="budget_id" value="<?php echo e($preparationRfq->budget_id ?? $selectedActivity?->budget_id); ?>">
                    <p class="mt-1 text-xs text-gray-500">Carried forward from the approved activity.</p>
                    <?php else: ?>
                    <label for="budget-search" class="block text-sm font-medium mb-1">Title / Subject *</label>
                    <input id="budget-search" type="search" x-model="budgetSearch" placeholder="Search budget activity or department" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm mb-2">
                    <select name="budget_id" x-model="budgetId" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Select a budget activity</option><template x-for="budget in filteredBudgets" :key="budget.id"><option :value="budget.id" x-text="budget.activity_title+' · '+(budget.department?.name || '')"></option></template></select>
                    <?php endif; ?>
                </div>
                <label class="text-sm font-medium">Quotation deadline<input type="date" name="deadline" value="<?php echo e(old('deadline')); ?>" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2"></label>
                <label class="text-sm font-medium sm:col-span-2">Instructions to vendors<textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2"><?php echo e(old('notes', $preparationRfq?->notes)); ?></textarea></label>
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
                        <div class="relative" @click.outside="item.open=false" x-show="!item.quotation_not_required"><label class="text-xs font-semibold text-gray-600">Vendors to invite *</label>
                            <button type="button" @click="item.open=!item.open" class="mt-1 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-left text-sm" x-text="item.vendor_ids.length ? item.vendor_ids.length+' vendors selected' : 'Choose vendors'"></button>
                            <div x-show="item.open" x-cloak class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white p-3 shadow-lg"><input type="search" x-model="item.search" placeholder="Search vendors" class="w-full border rounded px-2 py-1 text-sm"><div class="max-h-48 overflow-auto"><template x-for="vendor in filteredVendors(item.search)" :key="vendor.id"><label class="flex gap-2 py-2 text-sm"><input type="checkbox" x-model="item.vendor_ids" :value="vendor.id" @change="item.vendor_rate_id=''"><span x-text="vendor.name"></span></label></template></div></div>
                            <template x-for="id in item.vendor_ids" :key="id"><input type="hidden" :name="`items[${index}][vendor_ids][]`" :value="id"></template>
                        </div>
                        <button type="button" @click="items.splice(index,1)" class="mt-5 text-xs text-red-600" x-show="items.length>1 && !item.source_line_item_id">Remove</button>
                    </div>
                    <label class="block text-xs font-semibold text-gray-600">Request remarks / specification<textarea :name="`items[${index}][request_remarks]`" x-model="item.request_remarks" rows="2" class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Specifications, brand, delivery requirements…"></textarea></label>
                    <div class="flex flex-wrap gap-3 items-center">
                        <?php if($preparationRfq): ?><label class="text-sm" x-show="item.source_line_item_id"><input type="checkbox" :name="`items[${index}][quotation_not_required]`" value="1" x-model="item.quotation_not_required" @change="item.vendor_rate_id=''"> Quotation Not Required</label><?php endif; ?>
                        <select :name="`items[${index}][vendor_rate_id]`" x-model="item.vendor_rate_id" @change="applyRate(item)" x-show="!item.quotation_not_required" class="max-w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="">Request vendor quotations</option><template x-for="rate in ratesFor(item)" :key="rate.id"><option :value="String(rate.id)" x-text="'Approved rate: '+rate.item_name+' · '+rate.vendor_name+' · Rs '+rate.unit_rate+'/'+rate.unit+' · until '+rate.valid_until"></option></template></select>
                        <p x-show="!item.quotation_not_required && !item.vendor_ids.length" class="text-xs text-gray-500">Select a vendor to see their approved rates.</p>
                        <p x-show="!item.quotation_not_required && item.vendor_ids.length && !ratesFor(item).length" class="text-xs text-gray-500">No current approved rates for the selected vendors. Request quotations to continue.</p>
                        <p x-show="item.quotation_not_required" class="text-xs text-amber-800">Finance receives the original approved activity quantity and amount.</p>
                    </div>
                </div>
            </template></div>
        </section>
        <div class="flex gap-3"><button type="submit" class="btn-secondary">Save preparation</button><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.send')): ?><button name="send_now" value="1" type="submit" class="btn-primary">Save and send RFQ</button><?php endif; ?><a href="<?php echo e(route('rfq.index')); ?>" class="btn-quiet">Cancel</a></div>
    </form>
</div>
<script>
function rfqBuilder() {
    const initial=<?php echo \Illuminate\Support\Js::from($initialItems)->toHtml() ?>;
    return {
        budgets:<?php echo \Illuminate\Support\Js::from($budgetOptions)->toHtml() ?>, rates:<?php echo \Illuminate\Support\Js::from($catalogRates->map(fn($rate)=>array_merge($rate->only(['id','vendor_id','item_name','unit','unit_rate','specification']), ['vendor_name'=>$rate->vendor->name,'valid_until'=>$rate->valid_until->format('Y-m-d')])))->toHtml() ?>, vendors:<?php echo \Illuminate\Support\Js::from($vendorPayload)->toHtml() ?>, budgetSearch:'',budgetId:<?php echo \Illuminate\Support\Js::from(old('budget_id',$preparationRfq?->budget_id ?? ''))->toHtml() ?>,
        items:initial.map((item,i)=>({...item,key:i,quotation_not_required:!!item.quotation_not_required,vendor_rate_id:item.vendor_rate_id||'',vendor_ids:(item.vendor_ids||[]).map(String),open:false,search:''})),
        nextKey:initial.length,
        init(){this.items.forEach(item=>{const rate=this.rates.find(r=>String(r.id)===String(item.vendor_rate_id));if(rate)item.vendor_ids=[String(rate.vendor_id)];});},
        ratesFor(item){return this.rates.filter(r=>item.vendor_ids.includes(String(r.vendor_id)));},
        get filteredBudgets(){return this.budgets.filter(b=>b.id===this.budgetId || `${b.activity_title} ${b.department?.name}`.toLowerCase().includes(this.budgetSearch.toLowerCase()));},
        filteredVendors(search){return this.vendors.filter(v=>`${v.name} ${v.email}`.toLowerCase().includes(search.toLowerCase()));},
        addItem(){this.items.push({key:this.nextKey++,description:'',quantity:1,unit:'pcs',request_remarks:'',vendor_ids:[],vendor_rate_id:'',quotation_not_required:false,open:false,search:''});},
        applyRate(item){const r=this.rates.find(r=>String(r.id)===String(item.vendor_rate_id));if(r){item.vendor_ids=[String(r.vendor_id)];item.description=r.item_name;item.unit=r.unit;if(!item.request_remarks)item.request_remarks=r.specification||'';item.quotation_not_required=false;}}
    };
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/rfq/create.blade.php ENDPATH**/ ?>