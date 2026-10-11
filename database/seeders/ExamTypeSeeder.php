<?php

namespace Database\Seeders;

use App\Models\ExamType;
use Illuminate\Database\Seeder;

class ExamTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('exams.types', []) as $slug => $config) {
            ExamType::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $config['name'],
                    'name_ja' => $config['name_ja'],
                    'description' => $config['description'],
                    'selection_rules' => $config['selection_rules'],
                    'duration_minutes' => $config['duration_minutes'],
                    'max_score' => $config['max_score'],
                    'passing_score' => $config['passing_score'],
                    'is_active' => true,
                ]
            );
        }
    }
}
