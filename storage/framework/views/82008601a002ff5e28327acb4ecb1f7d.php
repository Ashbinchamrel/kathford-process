<?php $__env->startSection('title', 'Edit RFQ'); ?>
<?php $__env->startSection('page-title', 'Edit RFQ'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl space-y-4">

    <?php if($errors->any()): ?>
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 text-sm text-blue-800">
        Editing <span class="font-mono font-semibold"><?php echo e($rfq->rfq_number); ?></span> — only title, deadline and notes can be changed on an existing RFQ.
    </div>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.edit')): ?>
<form method="POST" action="<?php echo e(route('rfq.update', $rfq)); ?>">
        <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
        <?php echo method_field('PUT'); ?>

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <select name="budget_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select a budget activity…</option>
                    <?php $__currentLoopData = $budgets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $budget): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($budget->id); ?>" <?php if(old('budget_id', $rfq->budget_id ?: $rfq->activityForm?->budget_id) === $budget->id): echo 'selected'; endif; ?>><?php echo e($budget->activity_title); ?> · <?php echo e($budget->fiscal_year); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['budget_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quotation Deadline</label>
                <input type="date" name="deadline" value="<?php echo e(old('deadline', $rfq->deadline?->format('Y-m-d'))); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes / Instructions</label>
                <textarea name="notes" rows="4"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none resize-none"><?php echo e(old('notes', $rfq->notes)); ?></textarea>
            </div>
        </div>

        <div class="flex gap-3 mt-4">
            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white" style="background:#0B1E3D;">
                Save Changes
            </button>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rfq.view')): ?>
<a href="<?php echo e(route('rfq.show', $rfq)); ?>" class="text-gray-500 hover:text-gray-700 text-sm py-2.5">Cancel</a>
<?php endif; ?>
        </div>
    </form>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/rfq/edit.blade.php ENDPATH**/ ?>