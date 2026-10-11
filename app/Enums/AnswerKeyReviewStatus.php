<?php

namespace App\Enums;

enum AnswerKeyReviewStatus: string
{
    case VerificationPending = 'verification_pending';
    case Verified = 'verified';
    case Disputed = 'disputed';
    case RequiresFurtherResearch = 'requires_further_research';
    case PossibleIncorrect = 'possible_incorrect';
    case PossibleOutdatedRule = 'possible_outdated_rule';
    case AmbiguousOriginal = 'ambiguous_original';
    case SourceConflict = 'source_conflict';

    public function label(): string
    {
        return match ($this) {
            self::VerificationPending => 'Pending verification',
            self::Verified => 'Verified with evidence',
            self::Disputed => 'Disputed',
            self::RequiresFurtherResearch => 'Requires further research',
            self::PossibleIncorrect => 'Possible incorrect',
            self::PossibleOutdatedRule => 'Possible outdated rule',
            self::AmbiguousOriginal => 'Ambiguous original',
            self::SourceConflict => 'Source conflict',
        };
    }

    public function blocksPublication(): bool
    {
        return $this !== self::Verified;
    }
}
