<?php

namespace App\Services;

use App\Models\Image;
use App\Models\Tweet;
use Illuminate\Support\Facades\Storage;

class TweetImageService
{
    public function attachImage(Tweet $tweet, $image): void
    {
        $path = Storage::disk('public')->putFile('images', $image);

        $imageModel = new Image;
        $imageModel->name = basename($path);
        $imageModel->save();

        $tweet->images()->attach($imageModel->id);
    }

    public function deleteImage(Tweet $tweet, Image $image): void
    {
        $filePath = 'images/'.$image->name;

        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }

        $tweet->images()->detach($image->id);
        $image->delete();
    }
}
