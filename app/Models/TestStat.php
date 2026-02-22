<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestStat extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'test_id', 'views_count', 'attempts_count', 'completions_count',
        'average_score', 'average_time', 'pass_rate', 'difficulty_rating',
        'rating_avg', 'ratings_count', 'comments_count', 'bookmarks_count',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
}
