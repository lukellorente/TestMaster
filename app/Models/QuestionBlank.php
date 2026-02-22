<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionBlank extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question_id', 'blank_index', 'correct_answers', 'case_sensitive',
    ];

    protected function casts(): array
    {
        return [
            'correct_answers' => 'array',
            'case_sensitive' => 'boolean',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
