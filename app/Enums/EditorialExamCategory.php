<?php

namespace App\Enums;

enum EditorialExamCategory: string
{
    case GaimenKirikae = 'gaimen_kirikae';
    case Karimen = 'karimen';
    case Honmen = 'honmen';
    case GeneralTrafficRules = 'general_traffic_rules';
    case Unclassified = 'unclassified';

    public function label(): string
    {
        return match ($this) {
            self::GaimenKirikae => 'Gaimen Kirikae',
            self::Karimen => 'Karimen',
            self::Honmen => 'Honmen',
            self::GeneralTrafficRules => 'General traffic rules / study',
            self::Unclassified => 'Unclassified',
        };
    }

    /**
     * Propose a category from immutable source exam_family metadata.
     * Proposals are never auto-approved.
     */
    public static function proposeFromSourceExamFamily(string $examFamily): self
    {
        return match ($examFamily) {
            'karimen' => self::Karimen,
            'honmen' => self::Honmen,
            'gaimen_kirikae' => self::GaimenKirikae,
            default => self::Unclassified,
        };
    }
}
