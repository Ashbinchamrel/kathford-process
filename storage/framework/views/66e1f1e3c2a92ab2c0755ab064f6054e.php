<?php $__env->startSection('title', 'New Purchase Request'); ?>
<?php $__env->startSection('page-title', 'New Purchase Request'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-4xl" x-data="prBuilder()">
    <form method="POST" action="<?php echo e(route('purchase-requests.store')); ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 space-y-4">
            <h2 class="text-base font-semibold text-gray-800">1. Source</h2>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="pr_source" value="activity" id="src-activity" class="text-teal-500"
                           <?php echo e(old('pr_source', $activityForm || $approvedActivities->isNotEmpty() ? 'activity' : 'standalone') === 'activity' ? 'checked' : ''); ?>>
                    <span class="text-sm font-medium text-gray-700">From Approved Activity</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="pr_source" value="standalone" id="src-standalone" class="text-teal-500"
                           <?php echo e(old('pr_source', $activityForm || $approvedActivities->isNotEmpty() ? 'activity' : 'standalone') === 'standalone' ? 'checked' : ''); ?>>
                    <span class="text-sm font-medium text-gray-700">Standalone (Manual)</span>
                </label>
            </div>

            <div id="activity-section">
                <label class="block text-sm font-medium text-gray-700 mb-1">Select Approved Activity</label>
                <select name="activity_form_id" id="activity-select"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                    <option value="">— Select an approved activity —</option>
                    <?php $__currentLoopData = $approvedActivities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($activity->id); ?>" <?php echo e(old('activity_form_id', $activityForm?->id) == $activity->id ? 'selected' : ''); ?>>
                            <?php echo e($activity->form_number); ?> — <?php echo e($activity->activity_name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php if($approvedActivities->isEmpty()): ?>
                    <p class="text-xs text-gray-400 mt-1">No approved activity forms are available to link. You can still create a standalone purchase request.</p>
                <?php else: ?>
                    <p class="text-xs text-gray-400 mt-1">Selecting an activity loads its item list into this request.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4 space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="<?php echo e(old('title', $activityForm?->activity_name)); ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none" placeholder="Brief description of what you need">
                    <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Required By</label>
                    <input type="date" name="deadline_date" value="<?php echo e(old('deadline_date', $activityForm?->deadline_date?->format('Y-m-d'))); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Justification / Reason</label>
                    <textarea name="description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none"><?php echo e(old('description', $activityForm?->remarks)); ?></textarea>
                </div>
            </div>
        </div>

        
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-800">Items Required</h2>
                <button type="button" @click="addItem()" class="text-teal-600 hover:text-teal-700 text-sm font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Item
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold text-gray-500 uppercase border-b border-gray-100">
                            <th class="pb-2 pr-3">Description</th>
                            <th class="pb-2 pr-3 w-24">Unit</th>
                            <th class="pb-2 pr-3 w-24">Qty</th>
                            <th class="pb-2 pr-3 w-32">Est. Rate (Rs)</th>
                            <th class="pb-2 pr-3 w-36">Est. Amount</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, i) in items" :key="i">
                            <tr class="border-b border-gray-50">
                                <td class="py-2 pr-3">
                                    <input type="text" :name="`items[${i}][description]`" x-model="item.description" required placeholder="Item name / specification"
                                           class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 outline-none">
                                </td>
                                <td class="py-2 pr-3">
                                    <input type="text" :name="`items[${i}][unit]`" x-model="item.unit" placeholder="pcs"
                                           class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 outline-none">
                                </td>
                                <td class="py-2 pr-3">
                                    <input type="number" :name="`items[${i}][quantity]`" x-model.number="item.quantity" min="0.01" step="0.01" required @input="calc(i)"
                                           class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 outline-none">
                                </td>
                                <td class="py-2 pr-3">
                                    <input type="number" :name="`items[${i}][rate]`" x-model.number="item.rate" min="0" step="0.01" required @input="calc(i)"
                                           class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:ring-2 focus:ring-teal-400 outline-none">
                                </td>
                                <td class="py-2 pr-3 font-mono text-gray-600 text-right" x-text="item.amount ? 'Rs ' + item.amount.toLocaleString('en-IN', {minimumFractionDigits:2}) : '—'"></td>
                                <td class="py-2">
                                    <button type="button" @click="removeItem(i)" x-show="items.length > 1" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="pt-3 text-right text-sm font-semibold text-gray-600 pr-3">Estimated Total:</td>
                            <td class="pt-3 font-mono font-bold text-gray-800" x-text="'Rs ' + total.toLocaleString('en-IN', {minimumFractionDigits:2})"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-4">
            <h2 class="font-semibold text-gray-800 mb-3">Attachments</h2>
            <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png"
                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100">
            <p class="text-xs text-gray-400 mt-1">PDF or images, max <?php echo e(config('kathford.max_attachment_size_kb')); ?>KB each.</p>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-semibold transition-colors">Save Draft</button>
            <button type="submit" name="submit_now" value="1" class="px-6 py-2.5 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold transition-colors">Submit for Approval</button>
            <a href="<?php echo e(route('purchase-requests.index')); ?>" class="text-gray-500 hover:text-gray-700 text-sm py-2.5">Cancel</a>
        </div>
    </form>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function prBuilder() {
    const initial = <?php echo json_encode($initialItems, 15, 512) ?>;
    return {
        items: initial.length ? initial.map(item => ({ ...item, amount: item.quantity * item.rate })) : [{ description: '', unit: 'pcs', quantity: 1, rate: 0, amount: 0 }],
        get total() { return this.items.reduce((s, i) => s + (i.amount || 0), 0); },
        addItem() { this.items.push({ description: '', unit: 'pcs', quantity: 1, rate: 0, amount: 0 }); },
        removeItem(i) { this.items.splice(i, 1); },
        calc(i) { this.items[i].amount = (this.items[i].quantity || 0) * (this.items[i].rate || 0); }
    };
}

document.addEventListener('DOMContentLoaded', function () {
    const activitySource = document.getElementById('src-activity');
    const standaloneSource = document.getElementById('src-standalone');
    const activitySection = document.getElementById('activity-section');
    const activitySelect = document.getElementById('activity-select');

    function updateSource() {
        const isActivity = activitySource.checked;
        activitySection.classList.toggle('hidden', !isActivity);
        activitySelect.disabled = !isActivity;
        activitySelect.required = isActivity;
    }

    activitySource.addEventListener('change', updateSource);
    standaloneSource.addEventListener('change', updateSource);
    activitySelect.addEventListener('change', function () {
        if (this.value) {
            window.location.href = '<?php echo e(route('purchase-requests.create')); ?>?activity_form_id=' + encodeURIComponent(this.value);
        }
    });
    updateSource();
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/purchase-requests/create.blade.php ENDPATH**/ ?>