<?php

namespace Database\Seeders;

use App\Models\QuestionCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Traffic Signals',
            'Road Signs',
            'Road Markings',
            'Pedestrian Safety',
            'Right of Way',
            'Intersections',
            'Railroad Crossings',
            'Parking and Stopping',
            'Speed Limits',
            'Expressways',
            'Emergency Vehicles',
            'Hazard Prediction',
            'Vehicle Safety',
            'Driving Responsibilities',
        ];

        foreach ($categories as $index => $name) {
            QuestionCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'parent_id' => null,
                    'description' => "Demonstration category for {$name} study topics.",
                    'sort_order' => ($index + 1) * 10,
                ]
            );
        }
    }
}
