<?php $__env->startSection('title','Procurement Setup'); ?>
<?php $__env->startSection('page-title','Procurement Setup'); ?>
<?php $__env->startSection('content'); ?>
<div class="max-w-6xl space-y-5">
<section class="kcard p-5"><h2 class="font-semibold">RFQ preparation assignment</h2><form method="POST" action="<?php echo e(route('admin.procurement.assignment')); ?>" class="mt-3 space-y-3"><?php echo csrf_field(); ?><p class="text-sm text-gray-500">Choose who receives future approved activities. Only users with RFQ view and create permissions are listed. With no assignment, the activity creator and Super Admin retain access.</p><select name="rfq_preparer_user_id" class="w-full border rounded-lg p-2 text-sm"><option value="">No designated preparer</option><?php $__currentLoopData = $preparers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $preparer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($preparer->id); ?>" <?php if(\App\Models\Setting::get('rfq_preparer_user_id')===$preparer->id): echo 'selected'; endif; ?>><?php echo e($preparer->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><button class="btn-primary">Save assignment</button></form></section>
<section class="kcard p-5"><h2 class="text-lg font-semibold">Approved vendor rates</h2><p class="text-sm text-gray-500 mt-1">Approve fixed prices for a defined period. RFQ preparers can reuse current rates; quotation comparison and purchase-order approval still apply.</p>
<form method="POST" enctype="multipart/form-data" action="<?php echo e(route('admin.procurement.rates.import')); ?>" class="border rounded-lg p-4 mt-4 space-y-3">
<?php echo csrf_field(); ?><h3 class="font-semibold">Bulk upload from Excel</h3>
<a href="<?php echo e(route('admin.procurement.rates.template')); ?>" class="text-teal-700 underline text-sm">Download Excel template</a>
<p class="text-sm text-gray-500">Select one vendor for this file. Fill one rate per row using dates in YYYY-MM-DD format (Excel dates also work). Specification is optional; is_active accepts Yes/No and defaults to Yes. Maximum 1,000 rows / 5 MB. Matching item, unit and validity dates update the existing rate. Other rows add new rates. Errors prevent the entire upload.</p>
<label class="block text-sm">Vendor<select name="vendor_id" required class="block w-full border rounded-lg p-2"><option value="">Select vendor</option><?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($vendor->id); ?>"><?php echo e($vendor->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
<input type="file" name="rates_file" accept=".xlsx,.xls,.csv" required class="block text-sm"><button class="btn-primary">Upload and approve rates</button>
</form>
<div x-data="{vendorFilter:'', editing:null, vendorIds:<?php echo \Illuminate\Support\Js::from($rates->pluck('vendor_id')->unique()->values())->toHtml() ?>}" class="mt-4">
<label class="block text-sm font-medium">Show rates for vendor<select x-model="vendorFilter" @change="editing=null" class="block w-full border rounded-lg p-2"><option value="">Select vendor</option><?php $__currentLoopData = $vendors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vendor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($vendor->id); ?>"><?php echo e($vendor->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
<p x-show="!vendorFilter" class="mt-3 text-sm text-gray-500">Select a vendor to view approved rates.</p>
<div x-show="vendorFilter" x-cloak class="mt-4 overflow-x-auto border rounded-lg">
<table class="w-full text-sm text-left">
<thead class="bg-gray-50 text-gray-600"><tr>
<?php $__currentLoopData = ['Item','Unit','Unit rate (Rs)','Valid from','Valid until','Status','Actions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $heading): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th class="p-3 whitespace-nowrap"><?php echo e($heading); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</tr></thead>
<?php $__currentLoopData = $rates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<tbody x-show="vendorFilter === <?php echo \Illuminate\Support\Js::from($rate->vendor_id)->toHtml() ?>" x-cloak class="border-t">
<tr>
<td class="p-3 font-medium"><?php echo e($rate->item_name); ?><?php if($rate->specification): ?><p class="text-xs text-gray-500 font-normal mt-1"><?php echo e($rate->specification); ?></p><?php endif; ?></td>
<td class="p-3"><?php echo e($rate->unit); ?></td>
<td class="p-3 whitespace-nowrap"><?php echo e(number_format($rate->unit_rate,2)); ?></td>
<td class="p-3 whitespace-nowrap"><?php echo e($rate->valid_from->format('d M Y')); ?></td>
<td class="p-3 whitespace-nowrap"><?php echo e($rate->valid_until->format('d M Y')); ?></td>
<td class="p-3"><?php echo e(!$rate->is_active ? 'Inactive' : ($rate->valid_until->lt(today()) ? 'Expired' : ($rate->valid_from->gt(today()) ? 'Upcoming' : 'Active'))); ?></td>
<td class="p-3"><div class="flex gap-3 items-center">
<button type="button" class="text-teal-700 font-medium" @click="editing = editing === <?php echo e($rate->id); ?> ? null : <?php echo e($rate->id); ?>" :aria-expanded="editing === <?php echo e($rate->id); ?>">Edit</button>
<form method="POST" action="<?php echo e(route('admin.procurement.rates.destroy',$rate)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="text-red-600">Remove</button></form>
</div></td>
</tr>
<tr x-show="editing === <?php echo e($rate->id); ?>" x-cloak><td colspan="7" class="p-4 bg-gray-50">
<form method="POST" action="<?php echo e(route('admin.procurement.rates.update',$rate)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?> <?php echo $__env->make('admin.setup.rate-fields',['rate'=>$rate], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><button class="btn-primary mt-3">Approve changes</button><button type="button" @click="editing=null" class="ml-3 text-sm text-gray-600">Cancel</button></form>
</td></tr>
</tbody>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<tbody x-show="vendorFilter && !vendorIds.includes(vendorFilter)" x-cloak><tr><td colspan="7" class="p-4 text-gray-500">No approved rates saved for this vendor.</td></tr></tbody>
</table>
</div>
</div><details class="border rounded-lg p-4 mt-4"><summary class="cursor-pointer font-semibold text-sm">Add approved rate</summary><form method="POST" action="<?php echo e(route('admin.procurement.rates.store')); ?>" class="mt-4"><?php echo csrf_field(); ?> <?php echo $__env->make('admin.setup.rate-fields',['rate'=>null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><button class="btn-primary mt-3">Approve rate</button></form></details></section>
<section class="kcard p-5"><h2 class="text-lg font-semibold">Checklist questions</h2><p class="mt-1 text-sm text-gray-500">Manage goods and service controls separately. Saved checklist questions are preserved for audit history.</p>
<?php $__currentLoopData = $questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><details class="border rounded-lg p-3 mt-3"><summary class="cursor-pointer text-sm"><?php echo e(ucfirst($question->fulfillment_type)); ?> · <?php echo e($question->label); ?></summary><form method="POST" action="<?php echo e(route('admin.procurement.questions.update',$question)); ?>" class="mt-3"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?> <?php echo $__env->make('admin.setup.question-fields',['question'=>$question], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><button class="btn-primary mt-3">Save question</button></form><form method="POST" action="<?php echo e(route('admin.procurement.questions.destroy',$question)); ?>" class="mt-2"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="btn-danger">Remove question</button></form></details><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<form method="POST" action="<?php echo e(route('admin.procurement.questions.store')); ?>" class="mt-5"><?php echo csrf_field(); ?><h3 class="font-semibold mb-3">Add question</h3><?php echo $__env->make('admin.setup.question-fields',['question'=>null], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><button class="btn-primary mt-3">Add question</button></form></section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/admin/setup/procurement.blade.php ENDPATH**/ ?>