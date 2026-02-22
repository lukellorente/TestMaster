<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionStat extends Model
{
    public $timestamps = false;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'question_id', 'times_answered', 'times_correct',
        'times_incorrect', 'difficulty_score', 'average_time',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
