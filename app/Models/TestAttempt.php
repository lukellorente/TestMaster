<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'test_id', 'score', 'points_earned', 'total_points',
        'correct_answers', 'incorrect_answers', 'unanswered', 'time_spent',
        'is_completed', 'passed', 'started_at', 'completed_at',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
            'passed' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class, 'attempt_id');
    }
}
