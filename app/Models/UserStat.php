<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'tests_created', 'tests_completed', 'tests_passed',
        'average_score', 'total_study_time', 'perfect_scores',
        'current_streak', 'longest_streak', 'total_points',
        'badges_earned', 'followers_count', 'following_count',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
