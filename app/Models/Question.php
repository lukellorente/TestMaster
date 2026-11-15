<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    protected $fillable = [
        'test_id', 'type', 'content', 'explanation', 'points',
        'order_index', 'image_url', 'audio_url', 'video_url', 'time_limit',
        'numeric_answer', 'numeric_tolerance',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'numeric_answer' => 'float',
            'numeric_tolerance' => 'float',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('order_index');
    }

    public function blanks(): HasMany
    {
        return $this->hasMany(QuestionBlank::class)->orderBy('blank_index');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(QuestionOrderItem::class)->orderBy('correct_order');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuestionResponse::class);
    }
}
