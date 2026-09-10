<?php $__env->startSection('title', 'Payee Information'); ?>
<?php $__env->startSection('page-title', 'Payee Information'); ?>

<?php $__env->startSection('content'); ?>
<div class="mx-auto max-w-7xl space-y-6">
    <section class="rounded-2xl bg-[#0B1E3D] px-6 py-6 text-white shadow-sm sm:px-7">
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-300">Finance settings</p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight">Payees and payment accounts</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">Maintain who the college pays and the finance accounts used to group payment schedules. Bank details are available only to authorised finance users.</p>
    </section>

    <?php if(session('success')): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><?php echo e(session('success')); ?></div>
    <?php endif; ?>
    <?php if($errors->any()): ?>
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <p class="font-semibold">Please correct the highlighted information.</p>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p class="mt-1"><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <div class="grid gap-6 xl:grid-cols-5">
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-3">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m18-8a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" /></svg>
                    </span>
                    <div>
                        <h2 class="font-bold text-slate-800">Add payee</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Create a recipient for student, employee, supplier, government, or other payments.</p>
                    </div>
                </div>
            </div>
            <form method="POST" action="<?php echo e(route('admin.payees.store')); ?>" class="p-5 sm:p-6" id="payee-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="type" id="payee-type-value" value="<?php echo e(old('type')); ?>">
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label for="payee-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Payee name <span class="text-rose-500">*</span></label>
                        <input id="payee-name" required name="name" value="<?php echo e(old('name')); ?>" placeholder="e.g. Kathmandu Transport Service" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div>
                        <label for="payee-type-selector" class="mb-1.5 block text-sm font-semibold text-slate-700">Payee type <span class="text-rose-500">*</span></label>
                        <select id="payee-type-selector" required class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                            <option value="" disabled <?php echo e(old('type') ? '' : 'selected'); ?>>Select payee type</option>
                            <?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($type); ?>" <?php echo e(old('type') === $type ? 'selected' : ''); ?>><?php echo e($type); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <option value="__other__" <?php echo e(old('type') === '__other__' ? 'selected' : ''); ?>>Other — add a new type</option>
                        </select>
                        <p class="mt-1.5 text-xs text-slate-500">Choose a standard type or add a new one.</p>
                    </div>
                    <div id="custom-type-wrap" class="<?php echo e(old('type') === '__other__' ? '' : 'hidden'); ?>">
                        <label for="custom-type" class="mb-1.5 block text-sm font-semibold text-slate-700">New payee type <span class="text-rose-500">*</span></label>
                        <input id="custom-type" name="custom_type" value="<?php echo e(old('custom_type')); ?>" placeholder="e.g. Visiting Faculty" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div class="md:col-span-2 border-t border-slate-100 pt-5">
                        <p class="text-sm font-bold text-slate-800">Bank details <span class="font-normal text-slate-500">(optional)</span></p>
                        <p class="mt-1 text-xs text-slate-500">Enter these now, or add them later when the payee is ready for payment.</p>
                    </div>
                    <div>
                        <label for="bank-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Bank name</label>
                        <input id="bank-name" name="bank_name" value="<?php echo e(old('bank_name')); ?>" placeholder="e.g. Nabil Bank" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div>
                        <label for="account-number" class="mb-1.5 block text-sm font-semibold text-slate-700">Account number</label>
                        <input id="account-number" name="account_number" value="<?php echo e(old('account_number')); ?>" inputmode="numeric" autocomplete="off" placeholder="Account number" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div class="md:col-span-2">
                        <label for="account-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Account holder name</label>
                        <input id="account-name" name="account_name" value="<?php echo e(old('account_name')); ?>" placeholder="Name shown on the bank account" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                    </div>
                </div>
                <div class="mt-6 flex items-center justify-between gap-4 border-t border-slate-100 pt-5">
                    <p class="text-xs text-slate-500"><span class="text-rose-500">*</span> Required fields</p>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-teal-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-200">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Add payee
                    </button>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-6">
                <div class="flex items-start gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M5 6h14l2 14H3L5 6Zm4-3h6l1 3H8l1-3Z" /></svg>
                    </span>
                    <div>
                        <h2 class="font-bold text-slate-800">Payment accounts</h2>
                        <p class="mt-0.5 text-sm text-slate-500">These account names organise Payment Schedules and Authorisations.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-6">
                <form method="POST" action="<?php echo e(route('admin.payment-accounts.store')); ?>" class="grid gap-3 sm:grid-cols-5">
                    <?php echo csrf_field(); ?>
                    <div class="sm:col-span-3">
                        <label for="payment-account-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Account name <span class="text-rose-500">*</span></label>
                        <input id="payment-account-name" required name="name" value="<?php echo e(old('name')); ?>" placeholder="e.g. Supplier Payment" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="payment-account-code" class="mb-1.5 block text-sm font-semibold text-slate-700">Code</label>
                        <input id="payment-account-code" name="code" placeholder="e.g. SUP-001" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition placeholder:text-slate-400 focus:border-teal-500 focus:ring-2 focus:ring-teal-100">
                    </div>
                    <div class="sm:col-span-5"><button class="w-full rounded-lg bg-[#0B1E3D] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#102b55]">Add payment account</button></div>
                </form>

                <div class="mt-6 space-y-3">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Existing accounts</p>
                    <?php $__empty_1 = true; $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                            <form method="POST" action="<?php echo e(route('admin.payment-accounts.update', $account)); ?>" class="grid items-end gap-2 sm:grid-cols-5">
                                <?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?>
                                <div class="sm:col-span-3"><label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Account name</label><input required name="name" value="<?php echo e($account->name); ?>" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm"></div>
                                <div><label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500">Code</label><input name="code" value="<?php echo e($account->code); ?>" class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-sm"></div>
                                <div class="flex items-center justify-between gap-2"><label class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-600"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" <?php if($account->active): echo 'checked'; endif; ?> class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">Active</label><button class="text-sm font-semibold text-teal-700 hover:text-teal-800">Save</button></div>
                            </form>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="rounded-xl border border-dashed border-slate-300 px-4 py-7 text-center text-sm text-slate-500">No payment accounts have been added yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4 sm:flex sm:items-center sm:justify-between sm:px-6">
            <div><h2 class="font-bold text-slate-800">Saved payees</h2><p class="mt-1 text-sm text-slate-500">Bank account numbers are stored securely and are shown only to authorised administrators.</p></div>
            <span class="mt-2 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 sm:mt-0"><?php echo e($payees->total()); ?> total</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-[860px] w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500"><tr><th class="px-6 py-3.5">Payee</th><th class="px-5 py-3.5">Type</th><th class="px-5 py-3.5">Bank and account</th><th class="px-5 py-3.5">Status</th><th class="px-6 py-3.5 text-right">Manage</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $payees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-6 py-4 font-semibold text-slate-800"><?php echo e($payee->name); ?><p class="mt-1 text-xs font-normal text-slate-500"><?php echo e($payee->account_name ?: 'No account holder entered'); ?></p></td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700"><?php echo e($payee->type); ?></span></td>
                            <td class="px-5 py-4 text-slate-700"><?php echo e($payee->bank_name ?: '—'); ?><p class="mt-1 font-mono text-xs text-slate-500"><?php echo e($payee->account_number ?: 'No account number'); ?></p></td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?php echo e($payee->active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>"><?php echo e($payee->active ? 'Active' : 'Inactive'); ?></span></td>
                            <td class="px-6 py-4 text-right">
                                <details class="text-left"><summary class="inline-flex cursor-pointer rounded-lg px-3 py-2 text-sm font-semibold text-teal-700 hover:bg-teal-50">Edit</summary><form method="POST" action="<?php echo e(route('admin.payees.update', $payee)); ?>" class="mt-3 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-2"><?php echo csrf_field(); ?> <?php echo method_field('PUT'); ?><input name="name" value="<?php echo e($payee->name); ?>" required class="rounded-lg border-slate-300 text-sm"><input name="type" value="<?php echo e($payee->type); ?>" required class="rounded-lg border-slate-300 text-sm"><input name="bank_name" value="<?php echo e($payee->bank_name); ?>" placeholder="Bank name" class="rounded-lg border-slate-300 text-sm"><input name="account_number" value="<?php echo e($payee->account_number); ?>" placeholder="Account number" class="rounded-lg border-slate-300 text-sm"><input name="account_name" value="<?php echo e($payee->account_name); ?>" placeholder="Account holder name" class="rounded-lg border-slate-300 text-sm"><label class="flex items-center gap-2 text-sm text-slate-600"><input type="hidden" name="active" value="0"><input type="checkbox" name="active" value="1" <?php if($payee->active): echo 'checked'; endif; ?> class="rounded border-slate-300 text-teal-600">Active</label><div class="sm:col-span-2"><button class="rounded-lg bg-teal-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-teal-700">Save changes</button></div></form></details>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">No payees yet. Add the first payee above.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if($payees->hasPages()): ?><div class="border-t border-slate-100 px-5 py-3 sm:px-6"><?php echo e($payees->withQueryString()->links()); ?></div><?php endif; ?>
    </section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selector = document.getElementById('payee-type-selector');
    const hiddenType = document.getElementById('payee-type-value');
    const customWrap = document.getElementById('custom-type-wrap');
    const customType = document.getElementById('custom-type');
    if (!selector || !hiddenType || !customWrap || !customType) return;

    const syncType = function () {
        const isCustom = selector.value === '__other__';
        customWrap.classList.toggle('hidden', !isCustom);
        customType.required = isCustom;
        hiddenType.value = selector.value;
    };
    selector.addEventListener('change', syncType);
    syncType();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/admin/payees/index.blade.php ENDPATH**/ ?>