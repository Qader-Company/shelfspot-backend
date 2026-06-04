<?php

namespace App\Modules\V1\Users\Application\Services;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

class UserActivationChecker
{
    public static function isActive(User $user, PortalTypeEnum $portal): bool
    {
        return match ($portal) {
            PortalTypeEnum::COMPANY => self::isCompanyUserActive($user),
            PortalTypeEnum::ADMIN => self::isAdminActive($user),
            PortalTypeEnum::WORKER => self::isWorkerActive($user),
        };
    }

    private static function isCompanyUserActive(User $user): bool
    {
        $user->loadMissing('companyUser.company');

        return (bool) (
            $user->companyUser?->is_active
            && $user->companyUser?->company?->is_active
        );
    }

    private static function isAdminActive(User $user): bool
    {
        $user->loadMissing('admin');

        return (bool) $user->admin?->is_active;
    }

    private static function isWorkerActive(User $user): bool
    {
        $user->loadMissing('worker');

        return (bool) $user->worker?->is_active;
    }
}
