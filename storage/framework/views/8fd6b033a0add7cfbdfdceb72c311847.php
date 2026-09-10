<?php $__env->startSection('title', 'Permissions — ' . $user->name); ?>
<?php $__env->startSection('page-title', 'User Permissions'); ?>

<?php $__env->startSection('content'); ?>
<?php
    $templates = [
        'general' => ['label' => 'Requester', 'description' => 'Create and follow own activity forms.', 'tone' => 'teal'],
        'verifier' => ['label' => 'Verifier', 'description' => 'Review controls before an approval decision.', 'tone' => 'sky'],
        'approver' => ['label' => 'Approver', 'description' => 'Review and approve operational decisions.', 'tone' => 'violet'],
        'finance' => ['label' => 'Finance', 'description' => 'Manage checklists, schedules, and payment batches.', 'tone' => 'amber'],
        'none' => ['label' => 'Clear access', 'description' => 'Remove all individually granted permissions.', 'tone' => 'rose'],
    ];
?>

<div class="mx-auto max-w-7xl space-y-6">
    <section class="rounded-2xl bg-[#0B1E3D] px-6 py-6 text-white shadow-sm sm:px-7">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <nav class="mb-3 flex items-center gap-2 text-xs text-slate-300" aria-label="Breadcrumb">
                    <a href="<?php echo e(route('admin.users.index')); ?>" class="hover:text-white">Users</a><span>/</span>
                    <a href="<?php echo e(route('admin.users.show', $user)); ?>" class="hover:text-white"><?php echo e($user->name); ?></a><span>/</span>
                    <span class="text-white">Access</span>
                </nav>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-300">Access control</p>
                <h1 class="mt-2 text-2xl font-bold tracking-tight">Permissions for <?php echo e($user->name); ?></h1>
                <p class="mt-2 text-sm text-slate-300">Give access by procurement workflow, then refine individual actions where necessary.</p>
            </div>
            <div class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-sm">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-300">Assigned role</p>
                <p class="mt-1 font-bold text-white"><?php echo e($user->role?->label ?? 'No role assigned'); ?></p>
                <p class="mt-1 text-xs text-slate-300"><?php echo e($user->email); ?></p>
            </div>
        </div>
    </section>

    <?php if(session('success')): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"><?php echo e(session('success')); ?></div>
    <?php endif; ?>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div><h2 class="font-bold text-slate-800">Start with an access template</h2><p class="mt-1 text-sm text-slate-500">A template replaces the user’s current individual permissions. You can fine-tune it below.</p></div>
        </div>
        <form method="POST" action="<?php echo e(route('admin.permissions.template', $user)); ?>" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <?php echo csrf_field(); ?>
            <?php $__currentLoopData = $templates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $template): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php ($isClear = $key === 'none'); ?>
                <button type="submit" name="template" value="<?php echo e($key); ?>" onclick="return confirm('<?php echo e($isClear ? 'Remove all individually granted permissions for this user?' : 'Apply the '.$template['label'].' template? Existing individual permissions will be replaced.'); ?>')" class="group rounded-xl border p-4 text-left transition <?php echo e($isClear ? 'border-rose-200 bg-rose-50/50 hover:border-rose-300 hover:bg-rose-50' : 'border-slate-200 bg-white hover:border-teal-300 hover:bg-teal-50/30'); ?>">
                    <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-bold <?php echo e($isClear ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600 group-hover:bg-teal-100 group-hover:text-teal-800'); ?>"><?php echo e($template['label']); ?></span>
                    <span class="mt-3 block text-sm font-semibold text-slate-800"><?php echo e($template['description']); ?></span>
                    <span class="mt-3 block text-xs font-semibold <?php echo e($isClear ? 'text-rose-700' : 'text-teal-700'); ?>">Apply template →</span>
                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </form>
    </section>

    <form method="POST" action="<?php echo e(route('admin.permissions.update', $user)); ?>" id="permission-form">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4 sm:flex sm:items-center sm:justify-between sm:px-6">
                <div><h2 class="font-bold text-slate-800">Workflow permissions</h2><p class="mt-1 text-sm text-slate-500">Select only the actions this user needs. Role-based access remains subject to active workflow assignments.</p></div>
                <button type="button" id="clear-permissions" class="mt-3 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 sm:mt-0">Clear selections</button>
            </div>
            <div class="grid gap-px bg-slate-100 lg:grid-cols-2">
                <?php $__currentLoopData = $groupedPermissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php ($permissions = $group['permissions']); ?>
                    <?php ($selectedCount = $permissions->filter(fn ($permission) => in_array($permission->key, $userPermissionKeys))->count()); ?>
                    <section class="bg-white p-5 sm:p-6" data-module-card>
                        <div class="flex items-start justify-between gap-4">
                            <div><h3 class="font-bold text-slate-800"><?php echo e($group['meta']['title']); ?></h3><p class="mt-1 text-sm leading-5 text-slate-500"><?php echo e($group['meta']['description']); ?></p></div>
                            <span class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600" data-module-count><?php echo e($selectedCount); ?>/<?php echo e($permissions->count()); ?></span>
                        </div>
                        <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
                            <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label for="perm-<?php echo e($permission->id); ?>" class="flex cursor-pointer items-center justify-between gap-4 px-4 py-3 transition hover:bg-slate-50">
                                    <span class="text-sm font-medium text-slate-700"><?php echo e($permission->label); ?></span>
                                    <span data-toggle-track class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition <?php echo e(in_array($permission->key, $userPermissionKeys) ? 'bg-teal-600' : 'bg-slate-200'); ?>">
                                        <input class="peer sr-only module-<?php echo e($module); ?>" type="checkbox" name="permissions[]" value="<?php echo e($permission->id); ?>" id="perm-<?php echo e($permission->id); ?>" <?php echo e(in_array($permission->key, $userPermissionKeys) ? 'checked' : ''); ?>>
                                        <span class="absolute left-0.5 h-4 w-4 rounded-full bg-white shadow-sm transition peer-checked:translate-x-4"></span>
                                    </span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <button type="button" class="module-toggle mt-4 text-sm font-semibold text-teal-700 hover:text-teal-800" data-module="<?php echo e($module); ?>">Select all actions</button>
                    </section>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </section>
        <div class="sticky bottom-4 z-10 mt-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-lg backdrop-blur sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-600"><span id="selected-total" class="font-bold text-slate-800"><?php echo e(count($userPermissionKeys)); ?></span> individual permission<span id="permission-plural"><?php echo e(count($userPermissionKeys) === 1 ? '' : 's'); ?></span> selected</p>
            <div class="flex gap-3"><a href="<?php echo e(route('admin.users.index')); ?>" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</a><button type="submit" class="rounded-lg bg-teal-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-200">Save permissions</button></div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkboxes = Array.from(document.querySelectorAll('#permission-form input[type="checkbox"]'));
    const total = document.getElementById('selected-total');
    const plural = document.getElementById('permission-plural');

    function refreshCounts() {
        const selected = checkboxes.filter(box => box.checked).length;
        total.textContent = selected;
        plural.textContent = selected === 1 ? '' : 's';
        document.querySelectorAll('[data-module-card]').forEach(card => {
            const boxes = Array.from(card.querySelectorAll('input[type="checkbox"]'));
            const counter = card.querySelector('[data-module-count]');
            if (counter) counter.textContent = boxes.filter(box => box.checked).length + '/' + boxes.length;
            const toggle = card.querySelector('.module-toggle');
            if (toggle) toggle.textContent = boxes.length && boxes.every(box => box.checked) ? 'Clear all actions' : 'Select all actions';
        });
        checkboxes.forEach(box => {
            const track = box.closest('[data-toggle-track]');
            if (!track) return;
            track.classList.toggle('bg-teal-600', box.checked);
            track.classList.toggle('bg-slate-200', !box.checked);
        });
    }

    checkboxes.forEach(box => box.addEventListener('change', refreshCounts));
    document.querySelectorAll('.module-toggle').forEach(button => button.addEventListener('click', function () {
        const boxes = Array.from(document.querySelectorAll('.module-' + button.dataset.module));
        const allSelected = boxes.length && boxes.every(box => box.checked);
        boxes.forEach(box => box.checked = !allSelected);
        refreshCounts();
    }));
    document.getElementById('clear-permissions').addEventListener('click', function () {
        checkboxes.forEach(box => box.checked = false);
        refreshCounts();
    });
    refreshCounts();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/admin/permissions/edit.blade.php ENDPATH**/ ?>