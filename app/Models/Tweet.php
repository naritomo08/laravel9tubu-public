<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    use HasFactory;

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

    public function getVersionAttribute(): string
    {
        $this->loadMissing(['user', 'images']);

        $latestUpdatedAt = $this->updated_at;
        $userUpdatedAt = $this->user?->updated_at;

        if ($userUpdatedAt && $userUpdatedAt->gt($latestUpdatedAt)) {
            $latestUpdatedAt = $userUpdatedAt;
        }

        $imageSignature = $this->images
            ->sortBy('id')
            ->map(fn (Image $image) => implode(':', [
                $image->id,
                $image->name,
                $image->updated_at?->toJSON(),
            ]))
            ->implode(',');

        return $latestUpdatedAt->toJSON() . '|images:' . sha1($imageSignature);
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
