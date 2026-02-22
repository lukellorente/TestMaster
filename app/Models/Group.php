<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    protected $fillable = [
        'owner_id', 'name', 'description', 'avatar',
        'is_private', 'join_code', 'max_members', 'members_count',
    ];

    protected function casts(): array
    {
        return ['is_private' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_members')
                    ->withPivot('role', 'joined_at');
    }

    public function tests(): BelongsToMany
    {
        return $this->belongsToMany(Test::class, 'group_tests')
                    ->withPivot('added_by', 'added_at');
    }
}
