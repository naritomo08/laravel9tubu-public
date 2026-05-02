<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\Tweet;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestUserTweetsSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(TestUserSeeder::class);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $missingTweetCount = max(0, 60 - $user->tweets()->where('is_seeded', true)->count());

        Tweet::factory()
            ->count($missingTweetCount)
            ->create([
                'user_id' => $user->id,
                'is_seeded' => true,
            ])
            ->each(fn (Tweet $tweet) => Image::factory()
                ->count(4)
                ->create()
                ->each(fn (Image $image) => $tweet->images()->attach($image->id)));
    }
}
