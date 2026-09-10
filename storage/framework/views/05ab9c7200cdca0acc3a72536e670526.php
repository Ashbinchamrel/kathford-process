<?php $__env->startSection('title', 'Settings'); ?>
<?php $__env->startSection('page-title', 'Settings'); ?>
<?php $__env->startSection('content'); ?>
<div class="max-w-6xl">
    <p class="text-sm text-gray-500 mt-1 mb-5">Choose what you want to manage.</p>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('procurement_setup.manage')): ?>
        <a href="<?php echo e(route('admin.procurement.index')); ?>" class="kcard p-5 block hover:bg-teal-50">
            <h2 class="font-semibold text-teal-700">Procurement Setup →</h2>
            <p class="text-sm text-gray-500 mt-2">Approved vendor rates, Excel uploads, RFQ assignment and checklist questions.</p>
        </a>
        <?php endif; ?>
        <?php if(auth()->user()->isSuperAdmin()): ?>
        <?php $__currentLoopData = [
            ['admin.users.index','Users','User accounts, roles and permissions.'],
            ['admin.departments.index','Departments','Manage your organisation’s departments.'],
            ['admin.approval-chain.index','Approval Chains','Set verification and approval steps.'],
            ['admin.form-categories.index','Form Categories','Manage the types of activity forms.'],
            ['admin.payees.index','Payee Information','Payees and payment accounts.'],
            ['admin.profile.edit','Organisation Profile','Organisation details and fiscal years.'],
            ['admin.audit-logs.index','Audit Logs','Review recorded system activity.'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$route,$title,$description]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route($route)); ?>" class="kcard p-5 block hover:bg-teal-50">
            <h2 class="font-semibold text-teal-700"><?php echo e($title); ?> →</h2>
            <p class="text-sm text-gray-500 mt-2"><?php echo e($description); ?></p>
        </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/settings/index.blade.php ENDPATH**/ ?>