<?php

namespace App\Enums;

enum AccuracyEvidenceStatus: string
{
    case NotChecked = 'not_checked';
    case EvidenceCollected = 'evidence_collected';
    case NeedsClarification = 'needs_clarification';
    case ConflictingEvidence = 'conflicting_evidence';
    case HumanReviewed = 'human_reviewed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::NotChecked => 'Not checked',
            self::EvidenceCollected => 'Evidence collected',
            self::NeedsClarification => 'Needs clarification',
            self::ConflictingEvidence => 'Conflicting evidence',
            self::HumanReviewed => 'Human-reviewed',
            self::Rejected => 'Rejected',
        };
    }

    public function labelJa(): string
    {
        return match ($this) {
            self::NotChecked => '未確認',
            self::EvidenceCollected => '根拠収集済み',
            self::NeedsClarification => '要確認',
            self::ConflictingEvidence => '根拠が相反',
            self::HumanReviewed => '人による査読済み',
            self::Rejected => '却下',
        };
    }

    /**
     * Evidence collection alone never clears the answer-key publication gate.
     */
    public function confersAnswerKeyApproval(): bool
    {
        return false;
    }

    public function blocksPublicationReadiness(): bool
    {
        return $this !== self::HumanReviewed;
    }
}
