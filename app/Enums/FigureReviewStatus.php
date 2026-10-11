<?php

namespace App\Enums;

enum FigureReviewStatus: string
{
    case NotApplicable = 'not_applicable';
    case Pending = 'pending';
    case FigureMissing = 'figure_missing';
    case FigurePresentUnverified = 'figure_present_unverified';
    case ReviewedSufficient = 'reviewed_sufficient';
    case ReviewedInsufficient = 'reviewed_insufficient';

    public function label(): string
    {
        return match ($this) {
            self::NotApplicable => 'Not applicable',
            self::Pending => 'Pending figure review',
            self::FigureMissing => 'Essential figure missing',
            self::FigurePresentUnverified => 'Figure present, unverified',
            self::ReviewedSufficient => 'Figure reviewed (sufficient)',
            self::ReviewedInsufficient => 'Figure reviewed (insufficient)',
        };
    }

    public function labelJa(): string
    {
        return match ($this) {
            self::NotApplicable => '該当なし',
            self::Pending => '図版レビュー待ち',
            self::FigureMissing => '必須図版なし',
            self::FigurePresentUnverified => '図版あり・未検証',
            self::ReviewedSufficient => '図版確認済み（十分）',
            self::ReviewedInsufficient => '図版確認済み（不十分）',
        };
    }

    public function isReadyForPublication(): bool
    {
        return in_array($this, [self::NotApplicable, self::ReviewedSufficient], true);
    }

    public function blocksPublicationReadiness(): bool
    {
        return ! $this->isReadyForPublication();
    }
}
