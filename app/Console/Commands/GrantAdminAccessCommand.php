<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('menkyo:grant-admin {email : Email of an existing local account to authorize as administrator}')]
#[Description('Grant MenkyoNavi editorial administrator access to an existing user account')]
class GrantAdminAccessCommand extends Command
{
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Granting administrator access via Artisan is disabled in production.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email [{$email}]. Create/register the account first.");

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true])->save();

        $this->info("Granted administrator access to {$user->email} (user #{$user->id}).");
        $this->comment('Access the editorial UI at /admin/questions after signing in.');

        return self::SUCCESS;
    }
}
