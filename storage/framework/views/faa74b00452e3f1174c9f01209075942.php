<?php $__env->startSection('title', $vendor->name); ?>
<?php $__env->startSection('page-title', $vendor->name); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl space-y-5">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-start justify-between mb-5">
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?php echo e($vendor->name); ?></h2>
                <p class="text-gray-500 text-sm"><?php echo e($vendor->category); ?> · <?php echo e($vendor->company_type); ?></p>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo e($vendor->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'); ?>">
                    <?php echo e($vendor->is_active ? 'Active' : 'Inactive'); ?>

                </span>
                <?php if(auth()->user()->hasAnyRole(['super_admin','finance'])): ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendors.view')): ?>
<a href="<?php echo e(route('vendors.statement', $vendor)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Account Statement</a>
<?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendors.edit')): ?>
<a href="<?php echo e(route('vendors.edit', $vendor)); ?>" class="text-teal-600 hover:text-teal-700 text-sm font-medium">Edit</a>
<?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">PAN / VAT</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->pan_vat_number ?: '—'); ?></dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Owner</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->owner_name ?: '—'); ?></dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Contact Person</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->contact_person); ?></dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Mobile</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->mobile_number); ?></dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Office</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->office_number ?: '—'); ?></dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Email</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->email ?: '—'); ?></dd></div>
            <div class="sm:col-span-2"><dt class="text-xs text-gray-400 uppercase tracking-wide">Address</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->address); ?></dd></div>
            <?php if($vendor->notes): ?>
            <div class="sm:col-span-2"><dt class="text-xs text-gray-400 uppercase tracking-wide">Notes</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->notes); ?></dd></div>
            <?php endif; ?>
        </dl>
    </div>

    <?php if($showBankDetails): ?>
    <div class="bg-white rounded-xl border border-amber-200 p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Bank Details <span class="text-xs text-amber-600 font-normal">(Confidential – Finance &amp; Admin only)</span></h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Bank Name</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->bank_name ?: '—'); ?></dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Account Name</dt><dd class="text-sm text-gray-800 mt-1"><?php echo e($vendor->bank_account_name ?: '—'); ?></dd></div>
            <div><dt class="text-xs text-gray-400 uppercase tracking-wide">Account Number</dt><dd class="text-sm font-mono text-gray-800 mt-1"><?php echo e($vendor->bank_account_number ?: '—'); ?></dd></div>
        </dl>
    </div>
    <?php endif; ?>

    <?php if(auth()->user()->hasAnyRole(['super_admin','finance'])): ?>
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendors.edit')): ?>
<form method="POST" action="<?php echo e(route('vendors.portal-access', $vendor)); ?>" class="bg-white rounded-xl border border-gray-200 p-6">
        <?php echo csrf_field(); ?>
        <div class="flex items-start justify-between gap-4 mb-5">
            <div>
                <h3 class="font-semibold text-gray-800">Vendor Portal Access</h3>
                <p class="mt-1 text-sm text-gray-500">Set the approved email and initial password. The vendor can change their password after signing in.</p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?php echo e($vendor->portal_enabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'); ?>"><?php echo e($vendor->portal_enabled ? 'Enabled' : 'Disabled'); ?></span>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Approved login email <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="<?php echo e(old('email', $vendor->email)); ?>" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1"><?php echo e($vendor->portal_password ? 'Reset password (optional)' : 'Set initial password'); ?></label>
                <input type="password" name="portal_password" minlength="10" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
                <?php $__errorArgs = ['portal_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-red-500 text-xs mt-1"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm password</label>
                <input type="password" name="portal_password_confirmation" minlength="10" autocomplete="new-password" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 outline-none">
            </div>
            <div class="sm:col-span-2 flex items-center justify-between gap-4">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="portal_enabled" value="1" <?php echo e(old('portal_enabled', $vendor->portal_enabled) ? 'checked' : ''); ?> class="rounded text-teal-500">
                    <span class="text-sm font-medium text-gray-700">Enable vendor portal login</span>
                </label>
                <button class="px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm font-semibold">Save portal access</button>
            </div>
        </div>
    </form>
<?php endif; ?>
    <?php endif; ?>

    <div class="flex items-center gap-3">
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendors.view')): ?>
<a href="<?php echo e(route('vendors.index')); ?>" class="text-gray-400 hover:text-gray-600 text-sm">← Back to Vendors</a>
<?php endif; ?>
        <?php if(auth()->user()->hasAnyRole(['super_admin','finance'])): ?>
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('vendors.delete')): ?>
<form method="POST" action="<?php echo e(route('vendors.destroy', $vendor)); ?>" class="ml-auto"
              onsubmit="return confirm('Are you sure?')">
            <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-medium">
                <?php echo e($vendor->payments()->exists() || $vendor->rfqQuotes()->exists() ? 'Deactivate' : 'Delete'); ?>

            </button>
        </form>
<?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/vendors/show.blade.php ENDPATH**/ ?>