<?php $__env->startSection('title', 'Sign In'); ?>

<?php $__env->startSection('content'); ?>
<h2 class="text-2xl font-bold text-gray-900 mb-1">Sign in</h2>
<p class="text-sm text-gray-500 mb-7">Use your Kathford Process Portal account.</p>

<?php if(session('status')): ?>
    <div class="mb-5 rounded-lg px-4 py-3 text-sm" style="background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div class="mb-5 rounded-lg px-4 py-3 text-sm" style="background:#FFF1F2;border:1px solid #FECDD3;color:#991B1B;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('login.authenticate')); ?>">
    <?php echo csrf_field(); ?>
    <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
        <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="username"
               class="w-full rounded-lg px-3 py-2.5 text-sm outline-none" style="border:1.5px solid #D1D5DB;"
               onfocus="this.style.borderColor='#00A99D';" onblur="this.style.borderColor='#D1D5DB';">
    </div>
    <div class="mb-2">
        <div class="flex items-center justify-between mb-1.5">
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <a href="<?php echo e(route('password.request')); ?>" class="text-xs font-medium" style="color:#008C82;">Forgot password?</a>
        </div>
        <input id="password" type="password" name="password" required autocomplete="current-password"
               class="w-full rounded-lg px-3 py-2.5 text-sm outline-none" style="border:1.5px solid #D1D5DB;"
               onfocus="this.style.borderColor='#00A99D';" onblur="this.style.borderColor='#D1D5DB';">
    </div>
    <button type="submit" class="w-full mt-5 py-3 rounded-lg font-semibold text-white text-sm transition-colors"
            style="background:#00A99D;" onmouseover="this.style.background='#008C82';" onmouseout="this.style.background='#00A99D';">Sign in</button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/auth/login.blade.php ENDPATH**/ ?>