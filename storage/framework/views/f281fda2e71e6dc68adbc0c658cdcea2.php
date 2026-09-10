<?php $__env->startSection('title', 'Set Up Two-Factor Authentication'); ?>

<?php $__env->startSection('content'); ?>
<div class="mb-6">
    <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-4" style="background:#E6F7F6;">
        <svg class="w-5 h-5" style="color:#00A99D;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>
    <h2 class="text-xl font-bold text-gray-900 mb-1">Secure your account</h2>
    <p class="text-sm text-gray-500">Scan the QR code with Google Authenticator or Authy, then enter the 6-digit code to confirm.</p>
</div>


<div class="flex justify-center mb-4">
    <div class="p-3 rounded-xl inline-block" style="border:1.5px solid #E5E9EE;background:#fff;">
        <?php echo $qrSvg; ?>

    </div>
</div>


<div class="mb-5 rounded-lg p-3 text-center" style="background:#F8F9FB;border:1px solid #E5E9EE;">
    <p class="text-xs text-gray-400 mb-1">Can't scan? Enter this key manually:</p>
    <code class="text-sm font-mono font-bold text-gray-800 tracking-widest"><?php echo e($secret); ?></code>
</div>

<?php if($errors->any()): ?>
    <div class="mb-4 rounded-lg px-4 py-3 text-sm" style="background:#FFF1F2;border:1px solid #FECDD3;color:#991B1B;">
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><p><?php echo e($error); ?></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo e(route('2fa.setup.confirm')); ?>">
    <?php echo csrf_field(); ?>
    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Verification Code</label>
    <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
           placeholder="000 000" autofocus autocomplete="one-time-code"
           class="w-full text-center text-2xl font-mono tracking-widest rounded-lg px-4 py-3 outline-none mb-4"
           style="border:1.5px solid #D1D5DB;letter-spacing:0.2em;"
           onfocus="this.style.borderColor='#00A99D';" onblur="this.style.borderColor='#D1D5DB';">

    <button type="submit" class="w-full py-3 rounded-lg font-semibold text-white text-sm transition-colors"
            style="background:#00A99D;"
            onmouseover="this.style.background='#008C82';" onmouseout="this.style.background='#00A99D';">
        Verify &amp; Activate 2FA
    </button>
</form>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.auth', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/auth/2fa-setup.blade.php ENDPATH**/ ?>