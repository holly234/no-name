<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class SetPlatformOwner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform:owner {email : Existing user email} {--revoke : Remove platform-owner access}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Grant or revoke access to the private Perpetual Devs owner panel';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $this->error("No user exists with email {$email}.");

            return self::FAILURE;
        }

        $enabled = ! $this->option('revoke');
        $user->forceFill(['is_platform_owner' => $enabled])->save();

        $this->info($enabled
            ? "Platform-owner access granted to {$user->email}."
            : "Platform-owner access revoked from {$user->email}.");

        return self::SUCCESS;
    }
}
