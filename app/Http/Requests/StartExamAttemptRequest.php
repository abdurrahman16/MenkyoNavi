<?php

namespace App\Http\Requests;

use App\Enums\ExamAttemptType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartExamAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attempt_type' => [
                'required',
                Rule::enum(ExamAttemptType::class)->except([ExamAttemptType::AdminPreview]),
            ],
        ];
    }
}
