<?php $__env->startSection('title','Dashboard'); ?>
<?php $__env->startSection('page-title','Dashboard'); ?>
<?php $__env->startSection('content'); ?>
<div class="space-y-5">
    <div class="flex flex-wrap justify-between gap-3"><div><h2 class="text-xl font-semibold">Welcome, <?php echo e(auth()->user()->name); ?></h2><p class="mt-1 text-sm text-gray-500"><?php echo e($workingFiscalYear?->name); ?> · Your work and assigned actions</p></div><?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('activity_forms.create')): ?><a href="<?php echo e(route('activity-forms.create')); ?>" class="btn-primary">New Activity Form</a><?php endif; ?></div>
    <details class="kcard p-5"><summary class="font-semibold text-sm cursor-pointer">Customise dashboard reports</summary><form method="POST" action="<?php echo e(route('dashboard.widgets')); ?>" class="mt-4 space-y-4"><?php echo csrf_field(); ?><div class="grid gap-3 sm:grid-cols-3"><?php $__empty_1 = true; $__currentLoopData = $available; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><label class="flex gap-2 text-sm"><input type="checkbox" name="widgets[]" value="<?php echo e($key); ?>" <?php if(in_array($key,$selected)): echo 'checked'; endif; ?>><?php echo e($report[0]); ?></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p class="text-sm text-gray-500">No reports are available under your current permissions.</p><?php endif; ?></div><button class="btn-primary">Save dashboard</button></form></details>
    <div class="grid items-start gap-4 md:grid-cols-2 xl:grid-cols-3">
    <?php $__empty_1 = true; $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <section class="kcard overflow-hidden">
            <div class="p-4 <?php echo e($report['action_required'] && $report['count'] ? 'bg-amber-50' : 'bg-white'); ?>">
                <h2 class="text-sm font-semibold text-gray-700"><?php echo e($report['label']); ?></h2>
                <div class="mt-3 flex items-end justify-between gap-3">
                    <p class="text-3xl font-bold tabular-nums <?php echo e($report['action_required'] && $report['count'] ? 'text-amber-700' : 'text-slate-900'); ?>"><?php echo e(number_format($report['count'])); ?></p>
                    <span class="text-xs text-gray-500"><?php echo e($report['action_required'] ? ($report['count'] ? 'Needs your action' : 'You’re up to date') : 'This fiscal year'); ?></span>
                </div>
            </div>
            <?php if($report['count']): ?>
            <details class="border-t border-gray-100">
                <summary class="cursor-pointer px-4 py-3 text-xs font-semibold text-gray-600"><?php echo e($report['action_required'] ? 'Preview pending items' : 'Preview latest items'); ?> · <?php echo e(count($report['rows'])); ?></summary>
                <div class="divide-y divide-gray-100">
                    <?php $__currentLoopData = $report['rows']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <a href="<?php echo e($row['url']); ?>" class="block px-4 py-3 hover:bg-gray-50">
                        <p class="truncate text-sm font-medium text-gray-800" title="<?php echo e($row['title']); ?>"><?php echo e($row['title']); ?></p>
                        <p class="mt-1 text-xs text-gray-500"><?php echo e($row['status']); ?></p>
                    </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php if($report['count'] > count($report['rows'])): ?><p class="px-4 pb-3 text-xs text-gray-400">Showing <?php echo e(count($report['rows'])); ?> of <?php echo e($report['count']); ?>.</p><?php endif; ?>
            </details>
            <?php endif; ?>
            <a href="<?php echo e($report['module_url']); ?>" class="block border-t border-gray-100 px-4 py-3 text-xs font-semibold text-teal-700 hover:bg-teal-50"><?php echo e($report['action_required'] ? 'Open workflow queue' : 'Open module'); ?> →</a>
        </section>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="kcard p-6 text-sm text-gray-500 md:col-span-2">Choose reports under Customise dashboard reports to build your dashboard.</div>
    <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/dashboard.blade.php ENDPATH**/ ?>