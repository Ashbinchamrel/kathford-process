<?php

namespace App\Http\Controllers;

use App\Models\ActivityForm;
use App\Models\AuditLog;
use App\Models\FormAttachment;
use App\Models\FormCategory;
use App\Models\FormLineItem;
use App\Models\PurchaseRequest;
use App\Models\Vendor;
use App\Services\ApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function __construct(private ApprovalService $approvalService) {}

    public function index(Request $request): View
    {
        $user  = Auth::user();
        $query = PurchaseRequest::forUser($user)->with(['category', 'creator', 'activityForm', 'approvalChain']);

        if ($request->search) {
            $query->where('form_number', 'like', "%{$request->search}%")
                  ->orWhere('title', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('purchase-requests.index', compact('requests'));
    }

    public function create(Request $request): View
    {
        $category = FormCategory::where('code', 'PR')->firstOrFail();
        $approvedActivities = ActivityForm::where('status', ActivityForm::STATUS_APPROVED)
            ->with('lineItems')
            ->latest()
            ->get();

        $activityForm = $request->activity_form_id
            ? $approvedActivities->firstWhere('id', $request->activity_form_id)
            : null;
        $initialItems = $activityForm
            ? $activityForm->lineItems->map(fn (FormLineItem $item) => [
                'description' => $item->item_name,
                'unit'        => $item->unit,
                'quantity'    => (float) $item->quantity,
                'rate'        => (float) $item->rate,
            ])->values()
            : collect();

        return view('purchase-requests.create', compact(
            'category', 'approvedActivities', 'activityForm', 'initialItems'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pr_source'        => ['required', 'in:activity,standalone'],
            'title'            => ['required', 'string', 'max:255'],
            'activity_form_id' => ['nullable', 'required_if:pr_source,activity', 'exists:activity_forms,id'],
            'description'      => ['nullable', 'string'],
            'deadline_date'    => ['nullable', 'date'],
            'items'            => ['required', 'array', 'min:1'],
            'items.*.description'  => ['required', 'string'],
            'items.*.quantity'     => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'         => ['nullable', 'string', 'max:50'],
            'items.*.rate'         => ['required', 'numeric', 'min:0'],
            'items.*.vendor_id'    => ['nullable', 'exists:vendors,id'],
        ]);

        if ($request->pr_source === 'activity') {
            $activity = ActivityForm::where('status', ActivityForm::STATUS_APPROVED)
                ->findOrFail($request->activity_form_id);
        }

        DB::transaction(function () use ($request) {
            $category = FormCategory::where('code', 'PR')->firstOrFail();
            $chain    = $category->resolveChain();

            $pr = PurchaseRequest::create([
                'form_number'       => $category->nextFormNumber(),
                'category_id'       => $category->id,
                'activity_form_id'  => $request->activity_form_id,
                'creator_id'        => Auth::id(),
                'title'             => $request->title,
                'description'       => $request->description,
                'deadline_date'     => $request->deadline_date,
                'status'            => 'draft',
                'approval_chain_id' => $chain?->id,
            ]);

            foreach ($request->items as $item) {
                FormLineItem::create([
                    'itemable_type' => PurchaseRequest::class,
                    'itemable_id'   => $pr->id,
                    'item_name'     => $item['description'] ?? $item['item_name'] ?? '',
                    'quantity'      => $item['quantity'],
                    'unit'          => $item['unit'] ?? null,
                    'rate'          => $item['rate'],
                    'vendor_id'     => $item['vendor_id'] ?? null,
                ]);
            }

            $pr->recalculateTotal();
            AuditLog::record(Auth::user(), 'purchase_request.created', $pr, $pr->form_number);

            if ($request->boolean('submit_now')) {
                $this->approvalService->submit($pr, Auth::user());
            }

            session()->flash('success', "Purchase Request {$pr->form_number} created.");
            session()->flash('redirect_id', $pr->id);
        });

        $id = session('redirect_id');
        return redirect()->route('purchase-requests.show', $id);
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $this->authorizeView($purchaseRequest);
        $purchaseRequest->load([
            'category', 'creator', 'activityForm.category',
            'lineItems.vendor', 'verifierUser', 'approverUser',
            'approvalActions.actor', 'rfqs.quotes',
        ]);

        return view('purchase-requests.show', compact('purchaseRequest'));
    }

    public function edit(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->isEditable() && $purchaseRequest->creator_id === Auth::id(), 403);
        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();
        return view('purchase-requests.edit', compact('purchaseRequest', 'vendors'));
    }

    public function update(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->isEditable() && $purchaseRequest->creator_id === Auth::id(), 403);

        $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'items'       => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity'    => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'        => ['nullable', 'string', 'max:50'],
            'items.*.rate'        => ['required', 'numeric', 'min:0'],
            'items.*.vendor_id'   => ['nullable', 'exists:vendors,id'],
        ]);

        DB::transaction(function () use ($request, $purchaseRequest) {
            $purchaseRequest->update([
                'title'       => $request->title,
                'description' => $request->description,
            ]);

            $purchaseRequest->lineItems()->delete();
            foreach ($request->items as $item) {
                FormLineItem::create([
                    'itemable_type' => PurchaseRequest::class,
                    'itemable_id'   => $purchaseRequest->id,
                    'item_name'     => $item['description'] ?? $item['item_name'] ?? '',
                    'quantity'      => $item['quantity'],
                    'unit'          => $item['unit'] ?? null,
                    'rate'          => $item['rate'],
                    'vendor_id'     => $item['vendor_id'] ?? null,
                ]);
            }

            $purchaseRequest->recalculateTotal();
            AuditLog::record(Auth::user(), 'purchase_request.updated', $purchaseRequest, $purchaseRequest->form_number);

            if ($request->boolean('submit_now')) {
                $this->approvalService->submit($purchaseRequest, Auth::user());
            }
        });

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Purchase request updated.');
    }

    public function submit(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->creator_id === Auth::id() && $purchaseRequest->status === 'draft', 403);

        $this->approvalService->submit($purchaseRequest, Auth::user());
        AuditLog::record(Auth::user(), 'purchase_request.submitted', $purchaseRequest, $purchaseRequest->pr_number ?? $purchaseRequest->form_number);

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Purchase request submitted for approval.');
    }

    public function verify(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($this->approvalService->canVerify($purchaseRequest, Auth::user()), 403);

        $request->validate([
            'decision' => ['required', 'in:approved,modified_approved,rejected'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        $this->approvalService->verify($purchaseRequest, Auth::user(), $request->decision, $request->note, []);

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Decision recorded.');
    }

    public function approve(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($this->approvalService->canApprove($purchaseRequest, Auth::user()), 403);

        $request->validate([
            'decision' => ['required', 'in:approved,modified_approved,rejected'],
            'note'     => ['nullable', 'string', 'max:1000'],
        ]);

        $this->approvalService->approve($purchaseRequest, Auth::user(), $request->decision, $request->note, []);

        return redirect()->route('purchase-requests.show', $purchaseRequest)
            ->with('success', 'Decision recorded.');
    }

    public function destroy(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->isEditable() && $purchaseRequest->creator_id === Auth::id(), 403);

        AuditLog::record(Auth::user(), 'purchase_request.deleted', $purchaseRequest, $purchaseRequest->form_number);
        $purchaseRequest->delete();

        return redirect()->route('purchase-requests.index')
            ->with('success', 'Purchase request deleted.');
    }

    private function authorizeView(PurchaseRequest $pr): void
    {
        $user = Auth::user();
        if ($user->isSuperAdmin() || $user->isFinance()) return;
        if ($pr->creator_id === $user->id) return;
        if ($pr->verifier_id === $user->id) return;
        if ($pr->approver_id === $user->id) return;
        abort(403);
    }
}
