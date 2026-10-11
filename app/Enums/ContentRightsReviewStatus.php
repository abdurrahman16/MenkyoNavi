<?php

namespace App\Enums;

enum ContentRightsReviewStatus: string
{
    case Unclear = 'unclear';
    case PermissionRequired = 'permission_required';
    case PermissionObtained = 'permission_obtained';
    case ReviewPending = 'review_pending';
    case ApprovedForPublicReuse = 'approved_for_public_reuse';
    case Restricted = 'restricted';
    case DemoOnly = 'demo_only';

    public function label(): string
    {
        return match ($this) {
            self::Unclear => 'Rights unknown',
            self::PermissionRequired => 'Permission required',
            self::PermissionObtained => 'Permission obtained',
            self::ReviewPending => 'Review pending',
            self::ApprovedForPublicReuse => 'Reuse authorized',
            self::Restricted => 'Reuse not authorized',
            self::DemoOnly => 'Demo only',
        };
    }

    public function blocksPublication(): bool
    {
        return $this !== self::ApprovedForPublicReuse;
    }

    public function requiresEvidenceForApproval(): bool
    {
        return in_array($this, [
            self::PermissionObtained,
            self::ApprovedForPublicReuse,
        ], true);
    }
}
