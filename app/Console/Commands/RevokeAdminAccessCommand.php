<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('menkyo:revoke-admin {email : Email of the account that should lose administrator access}')]
#[Description('Revoke MenkyoNavi editorial administrator access from a user account')]
class RevokeAdminAccessCommand extends Command
{
    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Revoking administrator access via Artisan is disabled in production.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => false])->save();

        $this->info("Revoked administrator access from {$user->email} (user #{$user->id}).");

        return self::SUCCESS;
    }
}
