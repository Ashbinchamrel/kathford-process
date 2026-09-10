<?php $__env->startSection('title', 'Department Budgets'); ?>
<?php $__env->startSection('page-title', 'Department Budgets'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-[1600px] space-y-3" x-data="{ showCreate: false, editId: null }">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('budgets.manage')): ?><div class="flex justify-end gap-3"><button @click="showCreate = !showCreate" class="btn-primary">+ Add budget activity</button></div><?php endif; ?>


    <?php if($errors->any()): ?>
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <div x-show="showCreate" x-cloak class="kcard p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Create a budget activity</h3>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('budgets.manage')): ?>
<form method="POST" action="<?php echo e(route('budgets.store')); ?>" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Department <span class="text-red-500">*</span></label>
                <select name="department_id" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">Select department…</option>
                    <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($department->id); ?>" <?php if(old('department_id') === $department->id): echo 'selected'; endif; ?>><?php echo e($department->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fiscal year <span class="text-red-500">*</span></label>
                <input name="fiscal_year" value="<?php echo e($workingFiscalYear?->name); ?>" readonly required class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Title / Subject (Activity) <span class="text-red-500">*</span></label>
                <input name="activity_title" value="<?php echo e(old('activity_title')); ?>" required maxlength="200" placeholder="e.g. Annual IT Equipment Procurement" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Allocated budget (NPR) <span class="text-xs font-normal text-gray-400">optional</span></label>
                <input type="number" name="allocated_amount" value="<?php echo e(old('allocated_amount')); ?>" min="0" step="0.01" placeholder="Leave blank if not allocated" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-400">A Title / Subject can be used by Activity Forms without an allocation.</p>
            </div>
            <div class="flex items-end gap-3 pb-2">
                <input id="active" type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-teal-600">
                <label for="active" class="text-sm text-gray-700">Make available in Activity Forms</label>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                <textarea name="notes" rows="2" maxlength="2000" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Budget assumptions or internal reference…"><?php echo e(old('notes')); ?></textarea>
            </div>
            <div class="md:col-span-2 flex gap-3">
                <button class="btn-primary">Save budget activity</button>
                <button type="button" @click="showCreate=false" class="btn-secondary">Cancel</button>
            </div>
        </form>
<?php endif; ?>
    </div>

    <div class="kcard p-4 space-y-3">
        <form method="GET" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-4">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Find a budget activity</label>
                <div class="relative"><svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m20 20-4-4"/></svg><input type="search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Activity or department" class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm"></div>
            </div>
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Department</label>
                <select name="department_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All departments</option>
                    <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($department->id); ?>" <?php if(request('department_id') === $department->id): echo 'selected'; endif; ?>><?php echo e($department->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="lg:col-span-3">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Fiscal year</label>
                <input value="<?php echo e($workingFiscalYear?->name); ?>" readonly aria-label="Selected fiscal year" class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm"><p class="mt-1 text-xs text-gray-500">Change the year using the selector at the top of the page.</p>
            </div>
            <div class="lg:col-span-2"><button class="btn-primary w-full justify-center">Apply filters</button></div>
        </form>

        <details class="border-t border-gray-100 pt-3"><summary class="cursor-pointer text-sm font-semibold text-teal-700">Import budget activities</summary><div class="mt-3">
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('budgets.manage')): ?>
<form method="POST" action="<?php echo e(route('budgets.upload')); ?>" enctype="multipart/form-data" class="grid grid-cols-1 gap-3 lg:grid-cols-12 lg:items-end">
                <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
                <div class="lg:col-span-4">
                    <p class="text-sm font-semibold text-gray-800">Import budget activities</p>
                    <p class="mt-1 text-xs text-gray-500">Rows must use the selected active year. CSV headers: <code>department, fiscal_year, title</code>. Optional: <code>allocation, notes, active</code>.</p>
                </div>
                <div class="lg:col-span-6">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1.5">CSV file</label>
                    <input type="file" name="budget_file" accept=".csv,text/csv" required class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-md file:border-0 file:bg-teal-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-teal-700 hover:file:bg-teal-100">
                </div>
                <div class="lg:col-span-2"><button class="btn-secondary w-full justify-center">Upload CSV</button></div>
            </form>
<?php endif; ?>
        </div></details>
    </div>

    <div class="kcard overflow-hidden">
        <div class="overflow-x-auto">
            <table class="ktable min-w-[1120px] table-fixed">
                <colgroup>
                    <col class="w-[15%]"><col class="w-[12%]"><col class="w-[27%]"><col class="w-[12%]"><col class="w-[12%]"><col class="w-[14%]"><col class="w-[8%]"><col class="w-[72px]">
                </colgroup>
                <thead><tr><th class="px-5">Department</th><th class="px-5">Fiscal year</th><th class="px-5">Title / Subject</th><th class="px-5 text-right">Allocation</th><th class="px-5 text-right">Committed</th><th class="px-5 text-right">Available / variance</th><th class="px-5">Status</th><th class="px-5 text-right">Action</th></tr></thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $budgetRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($budget = $row['budget']); ?>
                    <tr>
                        <td class="px-5 font-medium break-words"><?php echo e($budget->department?->name); ?></td>
                        <td class="px-5 whitespace-nowrap"><?php echo e($budget->fiscal_year); ?></td>
                        <td class="px-5"><p class="font-medium break-words"><?php echo e($budget->activity_title); ?></p><?php if($budget->notes): ?><p class="text-xs text-gray-400 mt-1 break-words"><?php echo e($budget->notes); ?></p><?php endif; ?></td>
                        <td class="px-5 text-right tabular-nums whitespace-nowrap">Rs <?php echo e(number_format($budget->allocated_amount, 2)); ?></td>
                        <td class="px-5 text-right tabular-nums whitespace-nowrap">Rs <?php echo e(number_format($row['reserved'], 2)); ?></td>
                        <td class="px-5 text-right tabular-nums">
                            <?php if($row['remaining'] >= 0): ?>
                                <p class="font-semibold whitespace-nowrap text-green-700">Rs <?php echo e(number_format($row['remaining'], 2)); ?></p><p class="mt-0.5 text-xs text-gray-400">Available</p>
                            <?php else: ?>
                                <p class="font-semibold whitespace-nowrap text-red-600">Over by Rs <?php echo e(number_format(abs($row['remaining']), 2)); ?></p><p class="mt-0.5 text-xs text-red-500">Budget variance</p>
                            <?php endif; ?>
                        </td>
                        <td class="px-5"><span class="badge <?php echo e($budget->is_active ? 'badge-approved' : 'badge-draft'); ?>"><?php echo e($budget->is_active ? 'Active' : 'Inactive'); ?></span></td>
                        <td class="px-5 text-right"><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('budgets.manage')): ?><button @click="editId = editId === '<?php echo e($budget->id); ?>' ? null : '<?php echo e($budget->id); ?>'" class="text-teal-700 text-sm font-medium">Edit</button><?php endif; ?></td>
                    </tr>
                    <tr x-show="editId === '<?php echo e($budget->id); ?>'" x-cloak><td colspan="8" class="bg-gray-50">
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('budgets.manage')): ?>
<form method="POST" action="<?php echo e(route('budgets.update', $budget)); ?>" class="grid grid-cols-1 md:grid-cols-5 gap-3 p-2">
                            <?php echo csrf_field(); ?>
<input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>"> <?php echo method_field('PUT'); ?>
                            <select name="department_id" required class="rounded border border-gray-300 px-2 py-2 text-sm"><?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($department->id); ?>" <?php if($budget->department_id === $department->id): echo 'selected'; endif; ?>><?php echo e($department->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                            <input name="fiscal_year" value="<?php echo e($workingFiscalYear?->name); ?>" readonly required class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">fiscal_year }}" required class="rounded border border-gray-300 px-2 py-2 text-sm">
                            <input name="activity_title" value="<?php echo e($budget->activity_title); ?>" required class="rounded border border-gray-300 px-2 py-2 text-sm">
                            <input type="number" name="allocated_amount" value="<?php echo e($budget->allocated_amount ?: ''); ?>" min="0" step="0.01" placeholder="Optional allocation" class="rounded border border-gray-300 px-2 py-2 text-sm">
                            <div class="flex items-center gap-3"><label class="text-sm"><input type="checkbox" name="is_active" value="1" <?php if($budget->is_active): echo 'checked'; endif; ?>> Active</label><button class="btn-primary">Save</button></div>
                            <textarea name="notes" class="md:col-span-5 rounded border border-gray-300 px-2 py-2 text-sm" rows="2" placeholder="Notes"><?php echo e($budget->notes); ?></textarea>
                        </form>
<?php endif; ?>
                    </td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="8" class="text-center text-gray-400 py-12">No budget activities found. Create one or upload a CSV to begin.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/budgets/index.blade.php ENDPATH**/ ?>