<?php

namespace App\Enums;

enum QuestionImportDuplicateKind: string
{
    case None = 'none';
    case SameSourceReimport = 'same_source_reimport';
    case IdenticalAcrossSets = 'identical_across_sets';
    case SameWordingDifferentAnswer = 'same_wording_different_answer';
    case ExactCrossSourceMatch = 'exact_cross_source_match';
    case ExactQuestionDifferentAnswer = 'exact_question_different_answer';
    case SameQuestionDifferentExplanation = 'same_question_different_explanation';
    case SimilarWordingNeedsReview = 'similar_wording_needs_review';
    case DuplicateInsideSource = 'duplicate_inside_source';
}
