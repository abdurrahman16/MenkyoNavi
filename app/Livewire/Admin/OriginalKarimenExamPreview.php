<?php

namespace App\Livewire\Admin;

use App\Enums\ExamAttemptType;
use App\Exceptions\Exams\InsufficientExamQuestionsException;
use App\Models\ExamAttempt;
use App\Models\QuestionImportEntry;
use App\Services\Editorial\KarimenReleaseReadinessService;
use App\Services\Exams\OriginalKarimenAdminPreviewService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Original Karimen private exam preview')]
class OriginalKarimenExamPreview extends Component
{
    public string $statusMessage = '';

    public function mount(): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);
        abort_unless(Auth::user()?->isAdministrator() && Auth::user()?->email_verified_at !== null, 403);
    }

    public function startPreview(OriginalKarimenAdminPreviewService $preview): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);
        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator() && $user->email_verified_at !== null, 403);

        try {
            $attempt = $preview->start($user, forceRestart: false);
        } catch (InsufficientExamQuestionsException $exception) {
            $this->statusMessage = $exception->getMessage();

            return;
        }

        $this->redirect(route('exams.attempts.show', $attempt), navigate: false);
    }

    public function restartPreview(OriginalKarimenAdminPreviewService $preview): void
    {
        $this->authorize('viewAny', QuestionImportEntry::class);
        $user = Auth::user();
        abort_unless($user !== null && $user->isAdministrator() && $user->email_verified_at !== null, 403);

        try {
            $attempt = $preview->start($user, forceRestart: true);
        } catch (InsufficientExamQuestionsException $exception) {
            $this->statusMessage = $exception->getMessage();

            return;
        }

        $this->redirect(route('exams.attempts.show', $attempt), navigate: false);
    }

    public function render(
        OriginalKarimenAdminPreviewService $preview,
        KarimenReleaseReadinessService $readiness,
    ): View {
        $user = Auth::user();
        abort_unless($user !== null, 403);

        $open = $preview->openAttempt($user);
        $previewReadiness = $preview->readiness();
        $report = $readiness->report();

        return view('livewire.admin.original-karimen-exam-preview', [
            'openAttempt' => $open,
            'previewReadiness' => $previewReadiness,
            'releaseReport' => $report,
            'recentPreviews' => ExamAttempt::query()
                ->whereBelongsTo($user)
                ->where('attempt_type', ExamAttemptType::AdminPreview)
                ->with('examType')
                ->orderByDesc('id')
                ->limit(5)
                ->get(),
        ]);
    }
}
