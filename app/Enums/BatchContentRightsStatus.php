<?php

namespace App\Enums;

enum BatchContentRightsStatus: string
{
    case NoEvidenceSubmitted = 'no_evidence_submitted';
    case OriginIdentified = 'origin_identified';
    case OwnershipClaimed = 'ownership_claimed';
    case LicenseEvidenceSupplied = 'license_evidence_supplied';
    case PermissionDocumentSupplied = 'permission_document_supplied';
    case ReviewPending = 'review_pending';
    case ClearedByAuthorizedHuman = 'cleared_by_authorized_human';
    case RejectedNotPermitted = 'rejected_not_permitted';

    public function label(): string
    {
        return match ($this) {
            self::NoEvidenceSubmitted => 'No evidence submitted',
            self::OriginIdentified => 'Origin identified',
            self::OwnershipClaimed => 'Ownership claimed',
            self::LicenseEvidenceSupplied => 'License evidence supplied',
            self::PermissionDocumentSupplied => 'Permission document supplied',
            self::ReviewPending => 'Review pending',
            self::ClearedByAuthorizedHuman => 'Cleared by authorized human',
            self::RejectedNotPermitted => 'Rejected / not permitted',
        };
    }

    public function labelJa(): string
    {
        return match ($this) {
            self::NoEvidenceSubmitted => '根拠未提出',
            self::OriginIdentified => '出典特定済み',
            self::OwnershipClaimed => '権利主張あり',
            self::LicenseEvidenceSupplied => 'ライセンス根拠あり',
            self::PermissionDocumentSupplied => '許諾文書あり',
            self::ReviewPending => '査読待ち',
            self::ClearedByAuthorizedHuman => '権限ある人によるクリア',
            self::RejectedNotPermitted => '拒否／利用不可',
        };
    }

    public function isCleared(): bool
    {
        return $this === self::ClearedByAuthorizedHuman;
    }

    public function blocksPublication(): bool
    {
        return ! $this->isCleared();
    }
}
