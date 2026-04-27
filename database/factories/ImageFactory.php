<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // ディレクトリがなければ作成する
        if (!Storage::disk('public')->exists('images')) {
            Storage::disk('public')->makeDirectory('images');
        }

        $filename = Str::uuid() . '.svg';
        $label = Str::upper($this->faker->lexify('img???'));
        $backgroundColor = $this->faker->hexColor();
        $accentColor = $this->faker->hexColor();

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="480" viewBox="0 0 640 480">
  <rect width="640" height="480" fill="{$backgroundColor}" />
  <circle cx="540" cy="100" r="72" fill="{$accentColor}" fill-opacity="0.35" />
  <rect x="48" y="330" width="544" height="96" rx="20" fill="#ffffff" fill-opacity="0.18" />
  <text x="52%" y="46%" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="56" font-weight="700">Dummy Image</text>
  <text x="52%" y="60%" text-anchor="middle" fill="#ffffff" font-family="Arial, sans-serif" font-size="28">{$label}</text>
</svg>
SVG;

        Storage::disk('public')->put("images/{$filename}", $svg);

        return [
            'name' => $filename,
        ];
    }
}
