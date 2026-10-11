<?php

namespace App\Enums;

/**
 * Content-revision decisions for controlled Japanese source proposals.
 *
 * These are NOT human accuracy, editorial, rights, or publication approvals.
 */
enum SourceRevisionDecision: string
{
    case ApplyProposedRevision = 'apply_proposed_revision';
    case RequestAdditionalChanges = 'request_additional_changes';
    case RejectProposedRevision = 'reject_proposed_revision';
    case DeferPendingEvidence = 'defer_pending_evidence';

    public function label(): string
    {
        return match ($this) {
            self::ApplyProposedRevision => 'Apply proposed revision',
            self::RequestAdditionalChanges => 'Request additional changes',
            self::RejectProposedRevision => 'Reject proposed revision',
            self::DeferPendingEvidence => 'Defer pending evidence',
        };
    }

    public function labelJa(): string
    {
        return match ($this) {
            self::ApplyProposedRevision => '提案改訂を適用する',
            self::RequestAdditionalChanges => '追加の修正を依頼する',
            self::RejectProposedRevision => '提案改訂を却下する',
            self::DeferPendingEvidence => '保留（追加根拠待ち）',
        };
    }

    public function appliesContentChange(): bool
    {
        return $this === self::ApplyProposedRevision;
    }

    public function resolvesSourceWithholdForDrafting(): bool
    {
        return $this === self::ApplyProposedRevision;
    }

    public function requiresExplicitConfirmation(): bool
    {
        return $this === self::ApplyProposedRevision;
    }

    public function confersAccuracyApproval(): bool
    {
        return false;
    }

    public function confersEditorialApproval(): bool
    {
        return false;
    }

    public function confersRightsClearance(): bool
    {
        return false;
    }

    public function confersPublication(): bool
    {
        return false;
    }
}
