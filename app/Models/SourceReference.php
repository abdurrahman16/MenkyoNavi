<?php

namespace App\Models;

use App\Enums\CopyrightReuseStatus;
use App\Enums\SourceType;
use Database\Factories\SourceReferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceReference extends Model
{
    /** @use HasFactory<SourceReferenceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'issuing_organization',
        'source_type',
        'original_url',
        'edition_or_publication_date',
        'page_number',
        'last_verified_at',
        'copyright_reuse_status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_type' => SourceType::class,
            'copyright_reuse_status' => CopyrightReuseStatus::class,
            'last_verified_at' => 'date',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }
}
