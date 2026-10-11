<?php

namespace App\Http\Controllers;

use App\Enums\ExamAttemptStatus;
use App\Enums\ExamAttemptType;
use App\Models\ExamAttempt;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $recentAttempts = ExamAttempt::query()
            ->whereBelongsTo($user)
            ->where('attempt_type', '!=', ExamAttemptType::AdminPreview)
            ->with('examType')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $openAttempt = ExamAttempt::query()
            ->whereBelongsTo($user)
            ->where('attempt_type', '!=', ExamAttemptType::AdminPreview)
            ->where('status', ExamAttemptStatus::InProgress)
            ->where('expires_at', '>', now())
            ->with('examType')
            ->orderByDesc('id')
            ->first();

        $base = ExamAttempt::query()
            ->whereBelongsTo($user)
            ->where('attempt_type', '!=', ExamAttemptType::AdminPreview);

        $stats = [
            'completed_full' => (clone $base)
                ->where('attempt_type', ExamAttemptType::Full)
                ->whereIn('status', [ExamAttemptStatus::Submitted, ExamAttemptStatus::TimedOut])
                ->count(),
            'completed_demo' => (clone $base)
                ->where('attempt_type', ExamAttemptType::Demo)
                ->whereIn('status', [ExamAttemptStatus::Submitted, ExamAttemptStatus::TimedOut])
                ->count(),
            'open' => (clone $base)
                ->where('status', ExamAttemptStatus::InProgress)
                ->where('expires_at', '>', now())
                ->count(),
        ];

        return view('dashboard.index', [
            'recentAttempts' => $recentAttempts,
            'openAttempt' => $openAttempt,
            'stats' => $stats,
        ]);
    }
}
