<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Test extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'is_public', 'is_featured',
        'password', 'time_limit', 'max_attempts', 'pass_score', 'wrong_penalty',
        'shuffle_questions', 'shuffle_answers', 'show_correct_answers',
        'show_explanations', 'display_mode', 'difficulty', 'status', 'views_count',
        'attempts_count', 'average_score', 'published_at',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_featured' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_correct_answers' => 'boolean',
            'show_explanations' => 'boolean',
            'wrong_penalty' => 'float',
            'has_password' => 'boolean',
            'featured_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order_index');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'test_categories');
    }

    public function hasPassword(): bool
    {
        return ! empty($this->attributes['password'] ?? null);
    }

    /**
     * Borra el test y actualiza los contadores de sus categorías y de su autor
     * (no de quien lo borra: puede ser un admin).
     */
    public function deleteWithCounters(): void
    {
        DB::transaction(function () {
            $categoryIds = $this->categories()->pluck('categories.id')->all();
            if ($categoryIds) {
                Category::whereIn('id', $categoryIds)->where('tests_count', '>', 0)->decrement('tests_count');
            }

            if ($this->user_id) {
                UserStat::where('user_id', $this->user_id)->where('tests_created', '>', 0)->decrement('tests_created');
            }

            $this->delete();
        });
    }

}
