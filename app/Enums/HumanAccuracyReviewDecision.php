<?php

namespace App\Enums;

enum HumanAccuracyReviewDecision: string
{
    case Accepted = 'accuracy_accepted';
    case RevisionRequested = 'revision_requested';
    case Rejected = 'rejected_for_accuracy';
    case Deferred = 'deferred_evidence_needed';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Accuracy accepted',
            self::RevisionRequested => 'Revision requested',
            self::Rejected => 'Rejected for accuracy',
            self::Deferred => 'Deferred / evidence needed',
        };
    }

    public function labelJa(): string
    {
        return match ($this) {
            self::Accepted => '正確性を承認',
            self::RevisionRequested => '修正依頼',
            self::Rejected => '正確性により却下',
            self::Deferred => '保留（追加根拠が必要）',
        };
    }

    public function requiresNotes(): bool
    {
        return true;
    }

    public function requiresExplicitConfirmation(): bool
    {
        return $this === self::Accepted;
    }

    public function mapsToAccuracyEvidenceStatus(): AccuracyEvidenceStatus
    {
        return match ($this) {
            self::Accepted => AccuracyEvidenceStatus::HumanReviewed,
            self::RevisionRequested, self::Deferred => AccuracyEvidenceStatus::NeedsClarification,
            self::Rejected => AccuracyEvidenceStatus::Rejected,
        };
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

    public function confersAnswerKeyVerification(): bool
    {
        return false;
    }
}
