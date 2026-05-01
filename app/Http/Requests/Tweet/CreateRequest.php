<?php

namespace App\Http\Requests\Tweet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user() && $this->user()->hasVerifiedEmail();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $tweetMaxLength = config('tweet.content_max_length');

        return [
            'tweet' => 'required|max:'.$tweetMaxLength,
            'images' => 'array|max:4',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'page' => 'nullable|integer|min:1',
            'is_secret' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date',
        ];
    }
    // Requestクラスのuser関数で今自分がログインしているユーザーが取得できる
    public function userId(): int
    {
        return $this->user()->id;
    }

    public function tweet(): string
    {
        return $this->input('tweet');
    }
    public function images(): array
    {
        return $this->file('images', []);
    }

    public function isSecret(): bool
    {
        return $this->boolean('is_secret');
    }

    public function scheduledAt(): ?Carbon
    {
        $scheduledAt = $this->input('scheduled_at');

        if (! $scheduledAt) {
            return null;
        }

        return Carbon::parse($scheduledAt);
    }

    public function page(): int
    {
        return max(1, (int) $this->input('page', 1));
    }
}
