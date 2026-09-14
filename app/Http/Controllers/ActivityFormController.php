<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityFormRequest;
use App\Models\ActivityForm;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\FormAttachment;
use App\Models\FormCategory;
use App\Models\FormLineItem;
use App\Models\Payment;
use App\Services\ApprovalService;
use App\Services\UploadOptimizationService;
use App\Support\PaymentNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ActivityFormController extends Controller
{
    public function __construct(
        private readonly ApprovalService $approvalService,
        private readonly UploadOptimizationService $uploadOptimizer,
    ) {}

    private function nextPaymentNumber(): string
    {
        return PaymentNumber::next();
    }

    public function index(Request $request): View
    {
        $user  = Auth::user();
        $query = ActivityForm::with(['category', 'creator', 'department', 'approvalChain'])
                             ->forUser($user)
                             ->latest();

        // Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('form_number', 'like', "%{$request->search}%")
                  ->orWhere('activity_name', 'like', "%{$request->search}%");
            });
        }

        $forms      = $query->paginate(20)->withQueryString();
        $categories = FormCategory::active()->get();

        return view('activity-forms.index', compact('forms', 'categories'));
    }

    public function create(Request $request): View
    {
        $categories  = FormCategory::active()
                                   ->whereIn('category_group', ['activity'])
                                   ->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $selected    = $request->category_id
                        ? FormCategory::findOrFail($request->category_id)
                        : null;

        $budgetOptions = DepartmentBudget::active()->with('department')->orderBy('activity_title')->get()
            ->map(fn (DepartmentBudget $budget) => [
                'id' => $budget->id,
                'department_id' => $budget->department_id,
                'department_id' => $budget->department_id,
                'title' => $budget->activity_title,
                'fiscal_year' => $budget->fiscal_year,
                'allocated' => (float) $budget->allocated_amount,
                'reserved' => $budget->reservedAmount(),
                'remaining' => $budget->remainingAmount(),
            ])->values();

        $planningBudget = $request->filled('budget_id') ? DepartmentBudget::active()->find($request->budget_id) : null;
        return view('activity-forms.create', compact('categories', 'departments', 'selected', 'budgetOptions', 'planningBudget'));
    }

    public function store(ActivityFormRequest $request): RedirectResponse
    {
        if ($request->action === 'submit') Gate::authorize('activity_forms.submit');
        $user     = Auth::user();
        $category = FormCategory::findOrFail($request->category_id);
        $departmentId = $request->department_id ?? $user->department_id;
        $budget = $this->resolveBudget($request, $category, $departmentId);

        DB::transaction(function () use ($request, $user, $category, $departmentId, $budget, &$form) {
            $form = ActivityForm::create([
                'form_number'       => $category->nextFormNumber(),
                'category_id'       => $category->id,
                'creator_id'        => $user->id,
                'department_id'     => $departmentId,
                'budget_id'         => $budget?->id,
                'activity_name'     => $budget?->activity_title ?? $request->activity_name,
                'deadline_date'     => $request->deadline_date,
                'remarks'           => $request->remarks,
                'unplanned_reason'  => $category->requires_reason ? $request->unplanned_reason : null,
                'extra_field_values'=> $request->extra_fields ?? null,
                'status'            => 'draft',
            ]);

            // Save line items
            $this->saveLineItems($form, $request->line_items ?? []);

            // Save attachments
            $this->saveAttachments($form, $request);

            AuditLog::record($user, 'form.created', $form, $form->form_number);
        });

        if ($request->action === 'submit') {
            $this->submitWithBudgetCheck($form, $user);
            return redirect()->route('activity-forms.show', $form)
                ->with('success', "Form {$form->form_number} submitted for verification. Budget availability is shown for reference.");
        }

        return redirect()->route('activity-forms.show', $form)
            ->with('success', "Form {$form->form_number} saved as draft.");
    }

    public function show(ActivityForm $activityForm): View
    {
        $this->authorize('view', $activityForm);
        $activityForm->load([
            'category', 'creator', 'department',
            'verifier', 'approver', 'approvalActions.actor',
            'lineItems.vendor', 'attachments', 'rfqs.quotes', 'budget',
        ]);

        return view('activity-forms.show', ['form' => $activityForm]);
    }

    public function pdf(ActivityForm $activityForm): \Illuminate\Http\Response
    {
        $this->authorize('view', $activityForm);
        $activityForm->load([
            'category', 'creator', 'department', 'budget',
            'verifier', 'approver', 'approvalActions.actor',
            'lineItems.vendor',
        ]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'activity-forms.pdf',
            array_merge(['form' => $activityForm], \App\Support\DocumentBranding::data()),
        )->setPaper('a4', 'portrait');

        return $pdf->download("{$activityForm->form_number}.pdf");
    }

    public function edit(ActivityForm $activityForm): View
    {
        $this->authorize('update', $activityForm);
        $activityForm->load(['lineItems', 'attachments', 'category', 'budget']);
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        $categories = FormCategory::active()->where('category_group', 'activity')->get();
        $budgetOptions = DepartmentBudget::active()
            ->orderBy('activity_title')->get()
            ->map(fn (DepartmentBudget $budget) => [
                'id' => $budget->id,
                'department_id' => $budget->department_id,
                'title' => $budget->activity_title,
                'fiscal_year' => $budget->fiscal_year,
                'allocated' => (float) $budget->allocated_amount,
                'reserved' => $budget->reservedAmount($activityForm->id),
                'remaining' => $budget->remainingAmount($activityForm->id),
            ])->values();

        return view('activity-forms.edit', [
            'categories' => $categories,
            'form'        => $activityForm,
            'departments' => $departments,
            'budgetOptions' => $budgetOptions,
        ]);
    }

    public function update(ActivityFormRequest $request, ActivityForm $activityForm): RedirectResponse
    {
        if ($request->action === 'submit') Gate::authorize('activity_forms.submit');
        $this->authorize('update', $activityForm);
        $user = Auth::user();
        $activityForm->loadMissing('category');
        $category = FormCategory::active()->where('category_group', 'activity')->findOrFail($request->category_id);
        $departmentId = $request->department_id ?? $activityForm->department_id ?? $user->department_id;
        $budget = $this->resolveBudget($request, $category, $departmentId, $activityForm);

        DB::transaction(function () use ($request, $activityForm, $user, $departmentId, $budget, $category) {
            $activityForm->update([
                'category_id' => $category->id,
                'department_id'     => $departmentId,
                'budget_id'         => $budget?->id,
                'activity_name'     => $budget?->activity_title ?? $request->activity_name,
                'deadline_date'     => $request->deadline_date,
                'remarks'           => $request->remarks,
                'unplanned_reason'  => $category->requires_reason
                                        ? $request->unplanned_reason : null,
                'extra_field_values'=> $request->extra_fields ?? $activityForm->extra_field_values,
                'updated_by'        => $user->id,
            ]);

            // Replace line items
            $activityForm->lineItems()->delete();
            $this->saveLineItems($activityForm, $request->line_items ?? []);

            // Handle new attachments (existing are kept unless deleted)
            $this->saveAttachments($activityForm, $request);

            AuditLog::record($user, 'form.updated', $activityForm, $activityForm->form_number);
        });

        if ($request->action === 'submit') {
            $activityForm->update(['status' => 'draft']); // Reset so submit works
            $this->submitWithBudgetCheck($activityForm, $user);
            return redirect()->route('activity-forms.show', $activityForm)
                ->with('success', "Form {$activityForm->form_number} submitted for verification. Budget availability is shown for reference.");
        }

        return redirect()->route('activity-forms.show', $activityForm)
            ->with('success', 'Form updated successfully.');
    }


    /**
     * Submit a saved draft for verification (standalone POST route)
     */
    public function submit(ActivityForm $activityForm): RedirectResponse
    {
        $this->authorize('update', $activityForm);
        abort_unless($activityForm->status === 'draft', 403, 'Only draft forms can be submitted.');

        $this->submitWithBudgetCheck($activityForm, Auth::user());

        return redirect()->route('activity-forms.show', $activityForm)
            ->with('success', "Form {$activityForm->form_number} submitted for verification. Budget availability is shown for reference.");
    }

    /**
     * Verifier takes action (Layer 2)
     */
    public function verify(Request $request, ActivityForm $activityForm): RedirectResponse
    {
        $this->authorize('verify', $activityForm);

        $request->validate([
            'decision' => ['required', 'in:approved,modified_approved,rejected'],
            'note'     => ['nullable', 'string', 'max:2000',
                           'required_if:decision,rejected,modified_approved'],
        ]);

        $this->approvalService->verify($activityForm, Auth::user(), $request->decision, $request->note);

        return redirect()->route('activity-forms.show', $activityForm)
            ->with('success', 'Verification action recorded.');
    }

    /**
     * Approver takes action (Layer 3)
     */
    public function approve(Request $request, ActivityForm $activityForm): RedirectResponse
    {
        $this->authorize('finalApprove', $activityForm);

        $request->validate([
            'decision' => ['required', 'in:approved,modified_approved,rejected'],
            'note'     => ['nullable', 'string', 'max:2000',
                           'required_if:decision,rejected,modified_approved'],
        ]);

        $this->approvalService->approve($activityForm, Auth::user(), $request->decision, $request->note);

        return redirect()->route('activity-forms.show', $activityForm)
            ->with('success', 'Approval action recorded.');
    }

    // ── Attachment download ───────────────────────────────────

    public function downloadAttachment(ActivityForm $activityForm, string $attachment): mixed
    {
        $att = $activityForm->attachments()->findOrFail($attachment);
        $this->authorize('view', $activityForm);

        // External link — redirect to it (opens in browser)
        if ($att->external_link) {
            return redirect($att->external_link);
        }

        // File stored on private disk — stream inline so browser opens it
        $path = Storage::disk('private')->path($att->disk_path);
        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . $att->original_name . '"',
        ]);
    }

    public function deleteAttachment(ActivityForm $activityForm, string $attachment): RedirectResponse
    {
        $att = $activityForm->attachments()->findOrFail($attachment);
        $this->authorize('update', $activityForm);
        if ($att->disk_path) {
            Storage::disk('private')->delete($att->disk_path);
        }
        $att->delete();
        return back()->with('success', 'Attachment removed.');
    }

    // ── Private helpers ──────────────────────────────────────

    /** Resolve the selected departmental budget activity for every activity form. */
    private function resolveBudget(Request $request, FormCategory $category, ?string $departmentId, ?ActivityForm $existing = null): ?DepartmentBudget
    {
        $budgetId = $request->input('budget_id') ?: $existing?->budget_id;
        if (! $budgetId) {
            throw ValidationException::withMessages(['budget_id' => 'Choose a budget activity for this activity form.']);
        }

        $budget = DepartmentBudget::active()->find($budgetId);
        if (! $budget || $budget->department_id !== $departmentId) {
            throw ValidationException::withMessages(['budget_id' => 'Choose an active budget activity belonging to the selected department.']);
        }

        return $budget;
    }

    /**
     * Records the selected budget as an informational commitment. Budget availability
     * and variance are visible to users, but never change the approval workflow.
     */
    private function submitWithBudgetCheck(ActivityForm $form, \App\Models\User $user): bool
    {
        $form->loadMissing('budget', 'category');

        if ($form->budget) {
            $remaining = $form->budget->remainingAmount($form->id);
            $requested = (float) $form->total_estimated_amount;
            $variance = max(0, $requested - $remaining);
            $reason = $variance > 0
                ? 'Budget variance recorded for reference: requested NPR '.number_format($requested, 2)
                    .' against a remaining balance of NPR '.number_format($remaining, 2)
                    .'; variance NPR '.number_format($variance, 2).'.'
                : 'Budget availability recorded for reference.';

            $form->update([
                'budget_exception' => false,
                'budget_exception_reason' => null,
                'budget_checked_at' => now(),
            ]);
            AuditLog::record($user, $variance > 0 ? 'budget.variance_recorded' : 'budget.activity_committed', $form, $reason);
        }

        $this->approvalService->submit($form, $user);
        return false;
    }

    private function saveLineItems(ActivityForm $form, array $items): void
    {
        foreach ($items as $index => $item) {
            if (empty($item['item_name'])) continue;
            FormLineItem::create([
                'itemable_type' => ActivityForm::class,
                'itemable_id'   => $form->id,
                'item_name'     => $item['item_name'],
                'quantity'      => $item['quantity'] ?? 1,
                'unit'          => $item['unit'] ?? null,
                'rate'          => $item['rate'] ?? 0,
                'item_remarks'  => $item['item_remarks'] ?? null,
                'sort_order'    => $index,
            ]);
        }
        $form->recalculateTotal();
    }

    private function saveAttachments(ActivityForm $form, Request $request): void
    {
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $stored = $this->uploadOptimizer->store($file, "forms/{$form->id}");
                FormAttachment::create([
                    'attachable_type' => ActivityForm::class,
                    'attachable_id'   => $form->id,
                    'original_name'   => $stored['original_name'],
                    'disk_path'       => $stored['path'],
                    'mime_type'       => $stored['mime_type'],
                    'file_size'       => $stored['file_size'],
                    'uploaded_by'     => Auth::id(),
                ]);
            }
        }

        if ($request->external_link) {
            FormAttachment::create([
                'attachable_type' => ActivityForm::class,
                'attachable_id'   => $form->id,
                'original_name'   => 'External Link',
                'disk_path'       => '',
                'mime_type'       => 'text/uri-list',
                'file_size'       => 0,
                'external_link'   => $request->external_link,
                'type'            => 'link',
                'uploaded_by'     => Auth::id(),
            ]);
        }
    }
}
