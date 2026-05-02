<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tweet>
 */
class TweetFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'user_id' => 1, // つぶやきを投稿したユーザーのIDをデフォルトで1とする
            'content' => $this->contentAtMaxLength(),
            'is_secret' => false,
            'scheduled_at' => null,
            'created_at' => Carbon::now()->yesterday()
        ];
    }

    private function contentAtMaxLength(): string
    {
        $maxLength = max(1, (int) config('tweet.content_max_length', 140));
        $content = '';

        while (mb_strlen($content) < $maxLength) {
            $content .= $this->faker->realText(max(10, $maxLength));
        }

        return mb_substr($content, 0, $maxLength);
    }
}
