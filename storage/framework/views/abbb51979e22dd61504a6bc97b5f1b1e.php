<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['eyebrow' => 'Procurement workspace', 'title', 'description', 'actionUrl' => null, 'actionLabel' => null]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['eyebrow' => 'Procurement workspace', 'title', 'description', 'actionUrl' => null, 'actionLabel' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<section class="flex flex-col gap-4 rounded-2xl bg-[#0B1E3D] px-5 py-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between sm:px-7">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-300"><?php echo e($eyebrow); ?></p>
        <h2 class="mt-1 text-xl font-bold"><?php echo e($title); ?></h2>
        <p class="mt-1 text-sm text-slate-300"><?php echo e($description); ?></p>
    </div>
    <?php if($actionUrl && $actionLabel): ?>
        <a href="<?php echo e($actionUrl); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-400 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
            <?php echo e($actionLabel); ?>

        </a>
    <?php endif; ?>
</section>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/components/procurement-workspace-header.blade.php ENDPATH**/ ?>