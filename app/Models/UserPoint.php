<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPoint extends Model
{
    protected $fillable = [
        'user_id', 'points', 'level', 'experience',
        'streak_days', 'last_activity_date',
    ];

    protected function casts(): array
    {
        return ['last_activity_date' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
