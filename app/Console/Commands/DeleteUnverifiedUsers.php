<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Carbon;

class DeleteUnverifiedUsers extends Command
{
    protected $signature = 'users:delete-unverified';
    protected $description = 'Delete users who have not verified their email within 1 hour of registration.';

    public function handle()
    {
        $count = User::whereNull('email_verified_at')
            ->where('created_at', '<', now()->subHour())
            ->delete();
        $this->info("Deleted {$count} unverified users.");
    }
}
