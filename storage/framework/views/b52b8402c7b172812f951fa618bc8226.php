<?php $__env->startSection('title', 'Choose a New Password'); ?>

<?php $__env->startSection('content'); ?>
<h2 class="text-2xl font-bold text-gray-900 mb-1">Choose a new password</h2>
<p class="text-sm text-gray-500 mb-7">Use at least 12 characters.</p>

<?php if($errors->any()): ?>
    <div class="mb-5 rounded-lg px-4 py-3 text-sm" style="background:#FFF1F2;border:1px solid #FECDD3;color:#991B1B;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('password.update')); ?>">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="token" value="<?php echo e($token); ?>">
    <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
        <input id="email" type="email" name="email" value="<?php echo e(old('email', $email)); ?>" required autocomplete="email"
               class="w-full rounded-lg px-3 py-2.5 text-sm outline-none" style="border:1.5px solid #D1D5DB;">
    </div>
    <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">New password</label>
        <input id="password" type="password" name="password" required autocomplete="new-password"
               class="w-full rounded-lg px-3 py-2.5 text-sm outline-none" style="border:1.5px solid #D1D5DB;">
    </div>
    <div class="mb-5">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm new password</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
               class="w-full rounded-lg px-3 py-2.5 text-sm outline-none" style="border:1.5px solid #D1D5DB;">
    </div>
    <button type="submit" class="w-full py-3 rounded-lg font-semibold text-white text-sm transition-colors"
            style="background:#00A99D;" onmouseover="this.style.background='#008C82';" onmouseout="this.style.background='#00A99D';">Reset password</button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/auth/reset-password.blade.php ENDPATH**/ ?>