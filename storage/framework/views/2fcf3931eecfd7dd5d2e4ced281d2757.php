<?php $__env->startSection('title', 'Notifications'); ?>
<?php $__env->startSection('page-title', 'Notifications'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl space-y-4">
    <div class="flex items-center justify-between">
        <p class="text-sm text-gray-500"><?php echo e($notifications->total()); ?> notification<?php echo e($notifications->total() !== 1 ? 's' : ''); ?></p>
        <?php if($notifications->where('is_read', false)->count()): ?>
        <form method="POST" action="<?php echo e(route('notifications.mark-all-read')); ?>">
            <?php echo csrf_field(); ?>
            <button class="text-teal-600 hover:text-teal-700 text-sm font-medium">Mark all as read</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="space-y-2">
        <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notif): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <div class="bg-white rounded-xl border <?php echo e($notif->is_read ? 'border-gray-200' : 'border-teal-300 shadow-sm'); ?> p-4 flex items-start gap-4">
            <div class="w-9 h-9 rounded-full <?php echo e($notif->is_read ? 'bg-gray-100' : 'bg-teal-50'); ?> flex items-center justify-center flex-shrink-0">
                <?php switch($notif->type):
                    case ('form_submitted'): ?> 📋 <?php break; ?>
                    <?php case ('form_approved'): ?> ✅ <?php break; ?>
                    <?php case ('form_rejected'): ?> ❌ <?php break; ?>
                    <?php case ('form_verified'): ?> 🔍 <?php break; ?>
                    <?php case ('rfq_received'): ?> 💼 <?php break; ?>
                    <?php case ('po_issued'): ?> 📦 <?php break; ?>
                    <?php case ('payment_due'): ?> 💰 <?php break; ?>
                    <?php default: ?> 🔔
                <?php endswitch; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-800 <?php echo e($notif->is_read ? '' : 'font-medium'); ?>"><?php echo e($notif->message); ?></p>
                <p class="text-xs text-gray-400 mt-1"><?php echo e($notif->created_at->diffForHumans()); ?></p>
            </div>
            <div class="flex-shrink-0 flex items-center gap-3">
                <?php if(!$notif->is_read): ?>
                <form method="POST" action="<?php echo e(route('notifications.mark-read', $notif)); ?>">
                    <?php echo csrf_field(); ?>
                    <button class="text-xs text-teal-600 hover:text-teal-700 font-medium">Mark read</button>
                </form>
                <?php endif; ?>
                <?php if($notif->link): ?>
                <a href="<?php echo e(route('notifications.mark-read', ['notification' => $notif->id, 'redirect' => 1])); ?>" class="text-xs text-gray-500 hover:text-gray-700">View →</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
            <p class="text-gray-400 text-sm">You have no notifications.</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if($notifications->hasPages()): ?>
        <div><?php echo e($notifications->links()); ?></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/notifications/index.blade.php ENDPATH**/ ?>