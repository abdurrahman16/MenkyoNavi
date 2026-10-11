<?php

namespace App\Models;

use App\Enums\QuestionMediaType;
use Database\Factories\QuestionMediaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class QuestionMedia extends Model
{
    /** @use HasFactory<QuestionMediaFactory> */
    use HasFactory;

    protected $table = 'question_media';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'question_id',
        'disk',
        'path',
        'media_type',
        'alt_text',
        'mime_type',
        'width',
        'height',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'media_type' => QuestionMediaType::class,
            'width' => 'integer',
            'height' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function url(): string
    {
        if ($this->disk === 'web') {
            return asset(ltrim($this->path, '/'));
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
