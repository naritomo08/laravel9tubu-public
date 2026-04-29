<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\HtmlString;
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

    public function getFormattedContentAttribute(): HtmlString
    {
        $escapedContent = e((string) $this->content);

        $linkedContent = preg_replace_callback(
            '/((?:https?:\/\/|www\.)[^\s<]+)/iu',
            static function (array $matches): string {
                $detectedUrl = $matches[1];
                $linkedUrl = rtrim($detectedUrl, '.,!?;:)]}');
                $suffix = substr($detectedUrl, strlen($linkedUrl));

                if ($linkedUrl === '') {
                    return $detectedUrl;
                }

                $decodedUrl = html_entity_decode($linkedUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $href = str_starts_with($decodedUrl, 'www.') ? 'https://' . $decodedUrl : $decodedUrl;

                return sprintf(
                    '<a href="%s" target="_blank" rel="noopener noreferrer" class="text-sky-600 underline hover:text-sky-500 dark:text-sky-400 dark:hover:text-sky-300">%s</a>%s',
                    e($href),
                    $linkedUrl,
                    $suffix
                );
            },
            $escapedContent
        );

        return new HtmlString(nl2br($linkedContent ?? $escapedContent));
    }
}
