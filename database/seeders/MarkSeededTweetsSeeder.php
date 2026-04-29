<?php

namespace Database\Seeders;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Database\Seeder;

class MarkSeededTweetsSeeder extends Seeder
{
    /**
     * Mark existing tweets by seed admin users as seeded.
     */
    public function run(): void
    {
        $ids = collect(explode(',', (string) env('SEEDED_TWEET_IDS', '')))
            ->map(fn (string $id): int => (int) trim($id))
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            $updated = Tweet::whereIn('id', $ids)->update(['is_seeded' => true]);
            $this->command?->info("Marked {$updated} tweet(s) as seeded by explicit tweet IDs.");
            return;
        }

        $seedAdminIds = User::where('is_seed_admin', true)->pluck('id');

        if ($seedAdminIds->isEmpty()) {
            $this->command?->warn('No seed admin users found. Run UsersSeeder first.');
            return;
        }

        $updated = Tweet::whereIn('user_id', $seedAdminIds)
            ->where('is_seeded', false)
            ->update(['is_seeded' => true]);

        $this->command?->info("Marked {$updated} tweet(s) by seed admin user(s) as seeded.");
    }
}
