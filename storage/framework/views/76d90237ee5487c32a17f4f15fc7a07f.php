<?php $__env->startSection('title','Planning'); ?>
<?php $__env->startSection('page-title','Planning'); ?>
<?php $__env->startSection('content'); ?>
<style>.planning input:not([type=checkbox]),.planning select,.planning textarea{border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;width:100%;font-size:14px}.planning label{display:block;font-size:12px;color:#64748b;margin-bottom:5px}.planning th,.planning td{padding:12px;text-align:left;border-bottom:1px solid #e2e8f0}.planning th{font-size:12px;color:#64748b}.planning a{color:#0f766e}.planning .nav-active{background:#102444;color:white}.planning details>summary{cursor:pointer}.planning .task-card{border:1px solid #e2e8f0;background:white;padding:14px;border-radius:10px}</style>
<div class="planning space-y-4">
<nav class="kcard p-3 flex flex-wrap gap-2 text-sm">
<?php $__currentLoopData = \App\Services\Planning\Access::TYPES; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('planning.'.$key.'_view')): ?><a class="px-3 py-2 rounded-lg <?php echo e(request('type','strategy')===$key && !request()->routeIs('planning.tasks','planning.setup','planning.support') ? 'nav-active':''); ?>" href="<?php echo e(route('planning.index',['type'=>$key])); ?>"><?php echo e($label); ?></a><?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('planning.support')): ?><a class="px-3 py-2" href="<?php echo e(route('planning.support')); ?>">Support requests</a><?php endif; ?>
<a class="px-3 py-2" href="<?php echo e(route('planning.tasks')); ?>">Team work</a><a class="px-3 py-2" href="<?php echo e(route('planning.tasks',['mode'=>'calendar'])); ?>">My calendar</a>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('planning.setup')): ?><a class="px-3 py-2" href="<?php echo e(route('planning.setup')); ?>">Setup</a><?php endif; ?>
</nav>
<?php echo $__env->yieldContent('planning-content'); ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/planning/layout.blade.php ENDPATH**/ ?>