<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Tweet extends Model
{
    use HasFactory;

    protected $casts = [
        'is_secret' => 'boolean',
        'is_seeded' => 'boolean',
        'is_protected' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function images()
    {
        return $this->belongsToMany(Image::class, 'tweet_images')->using(TweetImage::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function likeCount(): int
    {
        return $this->likes()->count();
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user?->is_admin) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($user) {
            $query->where('is_secret', false);

            if ($user) {
                $query->orWhere('user_id', $user->id);
            }
        });
    }

    public function version(): string
    {
        $userUpdatedAt = $this->user?->updated_at?->toJSON();
        $imageVersions = $this->images
            ->sortBy('id')
            ->map(fn (Image $image) => [
                'id' => $image->id,
                'name' => $image->name,
                'updated_at' => $image->updated_at?->toJSON(),
            ])
            ->values()
            ->all();

        return sha1(json_encode([
            'tweet_updated_at' => $this->updated_at?->toJSON(),
            'is_secret' => (bool) $this->is_secret,
            'is_protected' => (bool) $this->is_protected,
            'user_updated_at' => $userUpdatedAt,
            'images' => $imageVersions,
        ]));
    }
}
