<?php

namespace App\Http\Requests\Tweet;

use App\Models\Tweet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
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
            'delete_image_ids' => 'array',
            'delete_image_ids.*' => 'integer',
            'page' => 'nullable|integer|min:1',
            'return_url' => 'nullable|string|max:2000',
            'is_secret' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $tweet = Tweet::with('images')->find($this->id());

            if (!$tweet) {
                return;
            }

            $deleteImageIds = collect($this->deleteImageIds());
            $remainingImageCount = $tweet->images
                ->reject(fn ($image) => $deleteImageIds->contains($image->id))
                ->count();

            if ($remainingImageCount + count($this->images()) > 4) {
                $validator->errors()->add('images', '画像は合計4枚まで指定できます。');
            }
        });
    }

    public function tweet(): string
    {
        return $this->input('tweet');
    }

    public function id(): int
    {
        return (int) $this->route('tweetId');
    }

    public function images(): array
    {
        return $this->file('images', []);
    }

    public function deleteImageIds(): array
    {
        return array_map('intval', $this->input('delete_image_ids', []));
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

    public function returnUrl(): ?string
    {
        return $this->input('return_url');
    }
}
