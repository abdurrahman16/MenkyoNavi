<?php

namespace App\Policies;

use App\Enums\ExamAttemptStatus;
use App\Models\ExamAttempt;
use App\Models\User;

class ExamAttemptPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExamAttempt $examAttempt): bool
    {
        if ($examAttempt->user_id !== $user->id) {
            return false;
        }

        if ($examAttempt->isAdminPreview()) {
            return $user->isAdministrator() && $user->email_verified_at !== null;
        }

        return true;
    }

    public function update(User $user, ExamAttempt $examAttempt): bool
    {
        return $this->view($user, $examAttempt)
            && $examAttempt->status === ExamAttemptStatus::InProgress
            && $examAttempt->expires_at->isFuture();
    }

    public function review(User $user, ExamAttempt $examAttempt): bool
    {
        return $this->view($user, $examAttempt) && $examAttempt->status->revealsAnswers();
    }
}
