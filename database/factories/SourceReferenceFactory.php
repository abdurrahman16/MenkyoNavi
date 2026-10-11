<?php

namespace Database\Factories;

use App\Enums\CopyrightReuseStatus;
use App\Enums\SourceType;
use App\Models\SourceReference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SourceReference>
 */
class SourceReferenceFactory extends Factory
{
    protected $model = SourceReference::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'issuing_organization' => 'MenkyoNavi Demo',
            'source_type' => SourceType::InternalDemo,
            'original_url' => null,
            'edition_or_publication_date' => null,
            'page_number' => null,
            'last_verified_at' => null,
            'copyright_reuse_status' => CopyrightReuseStatus::EducationalDemo,
            'notes' => 'Demonstration-only sample provenance. Not an officially verified document.',
        ];
    }
}
