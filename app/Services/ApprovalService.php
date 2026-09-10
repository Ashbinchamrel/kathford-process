<?php

namespace App\Services;

use App\Models\ActivityForm;
use App\Models\ApprovalAction;
use App\Models\ApprovalChain;
use App\Models\AuditLog;
use App\Models\PaymentAuthorisation;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    private const VERIFIER_LAYER_BASE = 100;
    private const APPROVER_LAYER_BASE = 200;

    public function __construct(private readonly NotificationService $notifications) {}

    /** Start a new sequential approval run with verifier layer 1. */
    public function submit(Model $form, User $actor): void
    {
        DB::transaction(function () use ($form, $actor) {
            $form = $this->lock($form);
            $chain = $this->resolveChain($form);
            if (! $chain || $chain->verifiers()->doesntExist() || $chain->approvers()->doesntExist()) {
                throw ValidationException::withMessages([
                    'approval_chain' => 'This document needs an active approval chain with at least one verifier and one approver before it can be submitted.',
                ]);
            }

            $form->update([
                'status' => 'pending_verification',
                'approval_chain_id' => $chain->id,
                'verifier_id' => null,
                'approver_id' => null,
            ]);
            $this->recordAction($form, $actor, 1, 'submitted', null);
            $this->notifyNextVerifier($form, $chain);
            AuditLog::record($actor, 'form.submitted', $form, $this->documentNumber($form));
        });
    }

    /** Record only the verifier whose ordered layer is currently open. */
    public function verify(Model $form, User $actor, string $decision, ?string $note, array $changes = []): void
    {
        DB::transaction(function () use ($form, $actor, $decision, $note, $changes) {
            $form = $this->lock($form);
            $chain = $form->approvalChain;
            $current = $this->currentVerifier($form);
            if (! $current || $current->id !== $actor->id) {
                throw ValidationException::withMessages(['approval' => 'This verification layer is assigned to another user.']);
            }

            $layer = $this->currentVerifierLayer($form);
            $newStatus = match ($decision) {
                'approved' => $this->verifierAt($chain, $layer + 1) ? 'pending_verification' : 'pending_approval',
                'modified_approved' => 'draft',
                'rejected' => 'rejected',
                default => throw new \InvalidArgumentException('Invalid verification decision.'),
            };

            $form->update([
                'status' => $newStatus,
                'verifier_id' => $actor->id,
                'verifier_decision' => $decision,
                'verifier_note' => $note,
                'verified_at' => now(),
            ]);
            $this->recordAction($form, $actor, self::VERIFIER_LAYER_BASE + $layer - 1, $decision, $note, $changes);

            if ($decision === 'approved' && $newStatus === 'pending_verification') {
                $this->notifyNextVerifier($form, $chain);
                $this->notifyOwner($form, 'Verification layer completed', "{$this->documentType($form)} {$this->documentNumber($form)} passed verifier layer {$layer} and is waiting for the next verifier.");
            } elseif ($decision === 'approved') {
                $this->notifyFirstApprover($form, $chain);
                $this->notifyOwner($form, 'Verification complete', "{$this->documentType($form)} {$this->documentNumber($form)} passed all verifier layers and is waiting for approval.");
            } else {
                $this->notifyOwner($form, $decision === 'rejected' ? 'Document rejected' : 'Changes requested', "{$this->documentType($form)} {$this->documentNumber($form)} was returned from verifier layer {$layer}.");
            }

            AuditLog::record($actor, "form.verified.{$decision}", $form, $this->documentNumber($form));
        });
    }

    /** Record only the approver whose ordered layer is currently open. */
    public function approve(Model $form, User $actor, string $decision, ?string $note, array $changes = []): void
    {
        DB::transaction(function () use ($form, $actor, $decision, $note, $changes) {
            $form = $this->lock($form);
            $chain = $form->approvalChain;
            $current = $this->currentApprover($form);
            if (! $current || $current->id !== $actor->id) {
                throw ValidationException::withMessages(['approval' => 'This approval layer is assigned to another user.']);
            }

            $layer = $this->currentApproverLayer($form);
            $newStatus = match ($decision) {
                'approved' => $this->approverAt($chain, $layer + 1) ? 'pending_approval' : 'approved',
                'modified_approved' => 'draft',
                'rejected' => 'rejected',
                default => throw new \InvalidArgumentException('Invalid approval decision.'),
            };

            $form->update([
                'status' => $newStatus,
                'approver_id' => $actor->id,
                'approver_decision' => $decision,
                'approver_note' => $note,
                'approved_at' => $newStatus === 'approved' ? now() : null,
            ]);
            $this->recordAction($form, $actor, self::APPROVER_LAYER_BASE + $layer - 1, $decision, $note, $changes);

            if ($decision === 'approved' && $newStatus === 'pending_approval') {
                $this->notifyNextApprover($form, $chain);
                $this->notifyOwner($form, 'Approval layer completed', "{$this->documentType($form)} {$this->documentNumber($form)} passed approver layer {$layer} and is waiting for the next approver.");
            } elseif ($decision === 'approved') {
                if ($form instanceof ActivityForm) {
                    $form->load('category');
                    if ($form->category?->bypasses_procurement_to_payment) app(ActivityPaymentService::class)->handoff($form);
                    else app(ActivityRfqService::class)->handoff($form);
                }
                $this->notifyOwner($form, 'Document approved', "{$this->documentType($form)} {$this->documentNumber($form)} has completed every approval layer.");
            } else {
                $this->notifyOwner($form, $decision === 'rejected' ? 'Document rejected' : 'Changes requested', "{$this->documentType($form)} {$this->documentNumber($form)} was returned from approver layer {$layer}.");
            }

            AuditLog::record($actor, "form.approved.{$decision}", $form, $this->documentNumber($form));
        });
    }

    public function canVerify(Model $form, User $user): bool
    {
        return $this->isPendingVerification($form) && ($user->isSuperAdmin() || $this->currentVerifier($form)?->id === $user->id);
    }

    public function canApprove(Model $form, User $user): bool
    {
        return $this->isPendingApproval($form) && ($user->isSuperAdmin() || $this->currentApprover($form)?->id === $user->id);
    }

    public function currentVerifier(Model $form): ?User
    {
        return $this->verifierAt($form->approvalChain, $this->currentVerifierLayer($form));
    }

    public function currentApprover(Model $form): ?User
    {
        return $this->approverAt($form->approvalChain, $this->currentApproverLayer($form));
    }

    public function currentVerifierLayer(Model $form): int
    {
        return $this->completedLayerCount($form, self::VERIFIER_LAYER_BASE) + 1;
    }

    public function currentApproverLayer(Model $form): int
    {
        return $this->completedLayerCount($form, self::APPROVER_LAYER_BASE) + 1;
    }

    public function pendingStepLabel(Model $form): ?string
    {
        if ($this->isPendingVerification($form)) {
            return 'Verifier layer '.$this->currentVerifierLayer($form).' · '.($this->currentVerifier($form)?->name ?: 'No assigned verifier');
        }

        if ($this->isPendingApproval($form)) {
            return 'Approver layer '.$this->currentApproverLayer($form).' · '.($this->currentApprover($form)?->name ?: 'No assigned approver');
        }

        return null;
    }

    private function completedLayerCount(Model $form, int $base): int
    {
        $submittedId = $this->actions($form)->where('decision', 'submitted')->max('id');
        $query = $this->actions($form)->whereBetween('layer', [$base, $base + 99])->where('decision', 'approved');
        if ($submittedId) {
            $query->where('id', '>', $submittedId);
        }
        return $query->count();
    }

    private function actions(Model $form)
    {
        return ApprovalAction::query()->where('actionable_type', $form::class)->where('actionable_id', $form->getKey());
    }

    /** Serialise each workflow transition to prevent duplicate layer actions. */
    private function lock(Model $form): Model
    {
        return $form::query()->whereKey($form->getKey())->lockForUpdate()->firstOrFail();
    }

    private function verifierAt(?ApprovalChain $chain, int $layer): ?User
    {
        return $chain?->verifiers()->get()->values()->get($layer - 1);
    }

    private function approverAt(?ApprovalChain $chain, int $layer): ?User
    {
        return $chain?->approvers()->get()->values()->get($layer - 1);
    }

    private function notifyNextVerifier(Model $form, ApprovalChain $chain): void
    {
        $layer = $this->currentVerifierLayer($form);
        $verifier = $this->verifierAt($chain, $layer);
        if ($verifier) {
            $this->notifications->send($verifier, 'form.pending_verification', 'Verification required', "{$this->documentType($form)} {$this->documentNumber($form)} requires your verification at verifier layer {$layer}.", $this->formRoute($form));
        }
    }

    private function notifyFirstApprover(Model $form, ApprovalChain $chain): void
    {
        $this->notifyNextApprover($form, $chain);
    }

    private function notifyNextApprover(Model $form, ApprovalChain $chain): void
    {
        $layer = $this->currentApproverLayer($form);
        $approver = $this->approverAt($chain, $layer);
        if ($approver) {
            $this->notifications->send($approver, 'form.pending_approval', 'Approval required', "{$this->documentType($form)} {$this->documentNumber($form)} requires your approval at approver layer {$layer}.", $this->formRoute($form));
        }
    }

    private function notifyOwner(Model $form, string $title, string $message): void
    {
        $owner = $this->owner($form);
        if ($owner) {
            $this->notifications->send($owner, 'form.workflow_updated', $title, $message, $this->formRoute($form));
        }
    }

    private function resolveChain(Model $form): ?ApprovalChain
    {
        if ($form instanceof ActivityForm && $form->category) return $form->category->resolveChain();
        if (($form instanceof PurchaseOrder || $form instanceof PaymentAuthorisation || $form instanceof PurchaseRequest) && $form->approval_chain_id) {
            return $form->approvalChain?->is_active ? $form->approvalChain : null;
        }
        return ApprovalChain::active()->where('is_default', true)->first();
    }

    private function isPendingVerification(Model $form): bool { return $form->status === 'pending_verification'; }
    private function isPendingApproval(Model $form): bool { return $form->status === 'pending_approval'; }

    private function recordAction(Model $form, User $actor, int $layer, string $decision, ?string $note, array $changes = []): void
    {
        ApprovalAction::create(['actionable_type' => $form::class, 'actionable_id' => $form->id, 'actor_id' => $actor->id, 'layer' => $layer, 'decision' => $decision, 'note' => $note, 'changes' => empty($changes) ? null : $changes, 'acted_at' => now(), 'ip_address' => request()->ip(), 'user_agent' => request()->userAgent()]);
    }

    private function formRoute(Model $form): string
    {
        return match (true) {
            $form instanceof PurchaseOrder => route('purchase-orders.show', $form),
            $form instanceof PaymentAuthorisation => route('payment-authorisations.show', $form),
            $form instanceof PurchaseRequest => route('purchase-requests.show', $form),
            default => route('activity-forms.show', $form),
        };
    }

    private function owner(Model $form): ?User
    {
        return match (true) {
            $form instanceof PurchaseOrder => $form->generatedBy,
            $form instanceof PaymentAuthorisation => $form->createdBy,
            default => $form->creator,
        };
    }

    private function documentNumber(Model $form): string
    {
        return match (true) {
            $form instanceof PurchaseOrder => $form->po_number,
            $form instanceof PaymentAuthorisation => $form->authorisation_number,
            default => $form->form_number,
        };
    }

    private function documentType(Model $form): string
    {
        return match (true) {
            $form instanceof PurchaseOrder => 'Purchase Order',
            $form instanceof PaymentAuthorisation => 'Payment Authorisation',
            $form instanceof PurchaseRequest => 'Purchase Request',
            default => 'Activity Form',
        };
    }
}
