<?php

namespace App\Policies;

use App\Models\QuestionImportEntry;
use App\Models\User;

class QuestionImportEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, QuestionImportEntry $questionImportEntry): bool
    {
        return $user->isAdministrator();
    }

    public function updateReview(User $user, QuestionImportEntry $questionImportEntry): bool
    {
        return $user->isAdministrator();
    }

    public function classify(User $user, QuestionImportEntry $questionImportEntry): bool
    {
        return $user->isAdministrator();
    }

    public function reviewDuplicate(User $user, QuestionImportEntry $questionImportEntry): bool
    {
        return $user->isAdministrator();
    }

    public function reviewAccuracy(User $user, QuestionImportEntry $questionImportEntry): bool
    {
        return $user->isAdministrator();
    }

    public function viewBatchRights(User $user, QuestionImportEntry $questionImportEntry): bool
    {
        return $user->isAdministrator();
    }
}
