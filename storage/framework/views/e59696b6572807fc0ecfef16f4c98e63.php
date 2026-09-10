<?php $__env->startSection('title', 'Forgot Password'); ?>

<?php $__env->startSection('content'); ?>
<h2 class="text-2xl font-bold text-gray-900 mb-1">Reset your password</h2>
<p class="text-sm text-gray-500 mb-7">Enter your email address and we’ll send a reset link.</p>

<?php if(session('status')): ?>
    <div class="mb-5 rounded-lg px-4 py-3 text-sm" style="background:#ECFDF5;border:1px solid #A7F3D0;color:#065F46;"><?php echo e(session('status')); ?></div>
<?php endif; ?>
<?php if($errors->any()): ?>
    <div class="mb-5 rounded-lg px-4 py-3 text-sm" style="background:#FFF1F2;border:1px solid #FECDD3;color:#991B1B;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('password.email')); ?>">
    <?php echo csrf_field(); ?>
    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email address</label>
    <input id="email" type="email" name="email" value="<?php echo e(old('email')); ?>" required autofocus autocomplete="email"
           class="w-full rounded-lg px-3 py-2.5 text-sm outline-none mb-5" style="border:1.5px solid #D1D5DB;"
           onfocus="this.style.borderColor='#00A99D';" onblur="this.style.borderColor='#D1D5DB';">
    <button type="submit" class="w-full py-3 rounded-lg font-semibold text-white text-sm transition-colors"
            style="background:#00A99D;" onmouseover="this.style.background='#008C82';" onmouseout="this.style.background='#00A99D';">Email reset link</button>
</form>
<p class="text-center text-sm text-gray-500 mt-5"><a href="<?php echo e(route('login')); ?>" class="font-medium" style="color:#008C82;">Back to sign in</a></p>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/auth/forgot-password.blade.php ENDPATH**/ ?>