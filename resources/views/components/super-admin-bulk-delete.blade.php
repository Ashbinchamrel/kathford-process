@props(['module'])

@if(auth()->user()?->isSuperAdmin() && ($fiscalYearWritable ?? true))
    <form id="bulk-delete-{{ $module }}" method="POST" action="{{ route('admin.transactions.bulk-delete', $module) }}" class="hidden items-center gap-3 border-b border-rose-100 bg-rose-50 px-5 py-3 sm:flex" data-bulk-delete-form="{{ $module }}">
        @csrf
        <input type="hidden" name="_fiscal_year_id" value="{{ $workingFiscalYear?->id }}">
        @method('DELETE')
        <input type="hidden" name="confirmation" value="DELETE">
        <span class="text-sm font-medium text-rose-800"><span data-bulk-delete-count="{{ $module }}">0</span> selected</span>
        <span class="hidden text-xs text-rose-700 sm:inline">Removed records remain in the audit trail. Paid financial records are protected.</span>
        <span data-bulk-delete-inputs="{{ $module }}"></span>
        <button type="submit" class="ml-auto rounded-lg border border-rose-300 bg-white px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-50" disabled data-bulk-delete-button="{{ $module }}">Delete selected</button>
    </form>

    @once
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
    @endonce
@endif
