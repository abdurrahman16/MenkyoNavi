<?php

namespace App\Http\Controllers;

use App\Models\UserQuestionMistake;
use Illuminate\View\View;

class MistakeVaultController extends Controller
{
    public function index(): View
    {
        $mistakes = UserQuestionMistake::query()
            ->whereBelongsTo(auth()->user())
            ->whereHas('question', fn ($query) => $query->where('is_demo', false))
            ->with(['question.category', 'question.translations', 'lastExamAttempt'])
            ->orderByDesc('last_incorrect_at')
            ->orderByDesc('id')
            ->get();

        return view('dashboard.mistakes', [
            'groups' => $mistakes->groupBy(
                fn (UserQuestionMistake $mistake): string => $mistake->question?->category?->name ?? 'General'
            ),
        ]);
    }
}
