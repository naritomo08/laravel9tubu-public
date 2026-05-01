<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use App\Models\User;

class Tweet extends Model
{
    use HasFactory;

    protected $casts = [
        'is_secret' => 'boolean',
        'is_seeded' => 'boolean',
        'is_protected' => 'boolean',
        'scheduled_at' => 'datetime',
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
        $query->where(function (Builder $query) {
            $query->whereNull('scheduled_at')
                ->orWhere('scheduled_at', '<=', now());
        });

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

    public function postedAt(): ?Carbon
    {
        return $this->scheduled_at ?? $this->created_at;
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
            'scheduled_at' => $this->scheduled_at?->toJSON(),
            'posted_at' => $this->postedAt()?->toJSON(),
            'is_secret' => (bool) $this->is_secret,
            'is_protected' => (bool) $this->is_protected,
            'user_updated_at' => $userUpdatedAt,
            'images' => $imageVersions,
        ]));
    }
}
