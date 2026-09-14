<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Support\Facades\DB;

class UserAccessRevoker
{
    /** @param array<int, string> $additionalEmails */
    public function revoke(User $user, array $additionalEmails = []): void
    {
        $user->tokens()->delete();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        DB::table('password_reset_tokens')
            ->whereIn('email', array_values(array_unique([$user->email, ...$additionalEmails])))
            ->delete();
    }
}
