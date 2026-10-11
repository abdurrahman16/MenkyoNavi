<?php

namespace App\Enums;

enum EditorialIssueType: string
{
    case AmbiguousEnglish = 'ambiguous_english';
    case PotentiallyOutdatedRule = 'potentially_outdated_traffic_rule';
    case SuspiciousAnswerKey = 'suspicious_answer_key';
    case MissingSourceEvidence = 'missing_source_evidence';
    case TranslationIssue = 'translation_issue';
    case CategoryUncertainty = 'question_category_uncertainty';
    case MediaDiagramIssue = 'media_or_diagram_issue';
    case ContentRightsUncertainty = 'content_rights_uncertainty';

    public function label(): string
    {
        return match ($this) {
            self::AmbiguousEnglish => 'Ambiguous English',
            self::PotentiallyOutdatedRule => 'Potentially outdated traffic rule',
            self::SuspiciousAnswerKey => 'Suspicious answer key',
            self::MissingSourceEvidence => 'Missing source evidence',
            self::TranslationIssue => 'Translation issue',
            self::CategoryUncertainty => 'Question category uncertainty',
            self::MediaDiagramIssue => 'Media or diagram issue',
            self::ContentRightsUncertainty => 'Content-rights uncertainty',
        };
    }
}
