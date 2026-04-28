<?php

namespace App\Http\Requests\Tweet;

use App\Models\Tweet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
        return [
            'tweet' => 'required|max:140',
            'page' => 'nullable|integer|min:1',
            'images' => 'array|max:4',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'delete_image_ids' => 'array',
            'delete_image_ids.*' => 'integer',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $tweet = Tweet::with('images')->find($this->id());

            if (!$tweet) {
                return;
            }

            $ownedDeleteImageCount = $tweet->images
                ->pluck('id')
                ->intersect($this->deleteImageIds())
                ->count();
            $imageCount = $tweet->images->count() - $ownedDeleteImageCount + count($this->images());

            if ($imageCount > 4) {
                $validator->errors()->add('images', '画像は4枚まで選択できます。');
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

    public function page(): int
    {
        return max(1, (int) $this->input('page', 1));
    }

    public function images(): array
    {
        return $this->file('images', []);
    }

    public function deleteImageIds(): array
    {
        return collect($this->input('delete_image_ids', []))
            ->map(fn ($imageId) => (int) $imageId)
            ->filter(fn ($imageId) => $imageId > 0)
            ->unique()
            ->values()
            ->all();
    }
}
