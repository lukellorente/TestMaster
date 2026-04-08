<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionResponse extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'attempt_id', 'question_id', 'given_answer',
        'is_correct', 'points_earned', 'time_spent', 'display_order',
    ];

    protected function casts(): array
    {
        return [
            'given_answer' => 'string',
            'is_correct' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
