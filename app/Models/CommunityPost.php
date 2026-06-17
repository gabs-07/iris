<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    protected $fillable = ['user_id', 'title', 'content', 'category', 'anonymous', 'status'];

    protected function casts(): array { return ['anonymous' => 'boolean']; }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function comments(): HasMany { return $this->hasMany(CommunityComment::class); }
    public function likes(): HasMany { return $this->hasMany(CommunityLike::class); }
    public function reports(): HasMany { return $this->hasMany(CommunityReport::class); }

    public function likedBy(?User $user): bool
    {
        return $user ? $this->likes()->where('user_id', $user->id)->exists() : false;
    }
}
