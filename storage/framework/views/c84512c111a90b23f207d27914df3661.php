<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['module']));

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

foreach (array_filter((['module']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if(auth()->user()?->isSuperAdmin() && ($fiscalYearWritable ?? true)): ?>
    <form id="bulk-delete-<?php echo e($module); ?>" method="POST" action="<?php echo e(route('admin.transactions.bulk-delete', $module)); ?>" class="hidden items-center gap-3 border-b border-rose-100 bg-rose-50 px-5 py-3 sm:flex" data-bulk-delete-form="<?php echo e($module); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="_fiscal_year_id" value="<?php echo e($workingFiscalYear?->id); ?>">
        <?php echo method_field('DELETE'); ?>
        <input type="hidden" name="confirmation" value="DELETE">
        <span class="text-sm font-medium text-rose-800"><span data-bulk-delete-count="<?php echo e($module); ?>">0</span> selected</span>
        <span class="hidden text-xs text-rose-700 sm:inline">Removed records remain in the audit trail. Paid financial records are protected.</span>
        <span data-bulk-delete-inputs="<?php echo e($module); ?>"></span>
        <button type="submit" class="ml-auto rounded-lg border border-rose-300 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50" disabled data-bulk-delete-button="<?php echo e($module); ?>">Delete selected</button>
    </form>

    <?php if (! $__env->hasRenderedOnce('fb651b91-2067-499b-9bbd-ffef59918cfa')): $__env->markAsRenderedOnce('fb651b91-2067-499b-9bbd-ffef59918cfa'); ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-bulk-delete-form]').forEach((form) => {
                    const module = form.dataset.bulkDeleteForm;
                    const checkboxes = () => Array.from(document.querySelectorAll(`[data-bulk-delete-record="${module}"]`));
                    const toggle = document.querySelector(`[data-bulk-delete-toggle="${module}"]`);
                    const count = form.querySelector(`[data-bulk-delete-count="${module}"]`);
                    const button = form.querySelector(`[data-bulk-delete-button="${module}"]`);
                    const inputContainer = form.querySelector(`[data-bulk-delete-inputs="${module}"]`);

                    const refresh = () => {
                        const selected = checkboxes().filter((checkbox) => checkbox.checked);
                        count.textContent = selected.length;
                        button.disabled = selected.length === 0;
                        if (toggle) {
                            toggle.checked = selected.length > 0 && selected.length === checkboxes().length;
                            toggle.indeterminate = selected.length > 0 && selected.length < checkboxes().length;
                        }
                    };

                    toggle?.addEventListener('change', () => {
                        checkboxes().forEach((checkbox) => checkbox.checked = toggle.checked);
                        refresh();
                    });
                    checkboxes().forEach((checkbox) => checkbox.addEventListener('change', refresh));

                    form.addEventListener('submit', (event) => {
                        const selected = checkboxes().filter((checkbox) => checkbox.checked);
                        if (selected.length === 0 || !window.confirm(`Remove ${selected.length} selected record${selected.length === 1 ? '' : 's'}? This is reversible, but the action will be recorded.`)) {
                            event.preventDefault();
                            return;
                        }
                        inputContainer.replaceChildren(...selected.map((checkbox) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'ids[]';
                            input.value = checkbox.value;
                            return input;
                        }));
                    });

                    refresh();
                });
            });
        </script>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH /Users/ashbinkumarchamrel/Downloads/kathford-process/resources/views/components/super-admin-bulk-delete.blade.php ENDPATH**/ ?>