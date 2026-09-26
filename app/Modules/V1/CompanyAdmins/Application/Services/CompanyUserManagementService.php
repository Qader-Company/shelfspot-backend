<?php

namespace App\Modules\V1\CompanyAdmins\Application\Services;

use App\Modules\V1\AccessControl\Application\Services\FullAccessRoleProvisioner;
use App\Modules\V1\AccessControl\Application\Services\PermissionCatalog;
use App\Modules\V1\AccessControl\Domain\Repositories\AccessControlRepositoryInterface;
use App\Modules\V1\CompanyAdmins\Domain\Models\CompanyUser;
use App\Modules\V1\CompanyAdmins\Domain\Repositories\CompanyUserRepositoryInterface;
use App\Modules\V1\Users\Application\Services\UserAccessRevoker;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\Repositories\UserRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CompanyUserManagementService
{
    public function __construct(
        private readonly CompanyUserRepositoryInterface $companyUsers,
        private readonly UserRepositoryInterface $users,
        private readonly AccessControlRepositoryInterface $accessControl,
        private readonly UserAccessRevoker $accessRevoker,
    ) {}

    public function users(int $companyId, array $filters = []): Collection
    {
        return $this->companyUsers->users($companyId, $filters);
    }

    public function find(int $companyId, int $userId): User
    {
        return $this->companyUsers->findUser($companyId, $userId);
    }

    public function create(
        int $companyId,
        array $attributes,
        bool $isOwner = false,
        bool $markEmailVerified = true,
    ): User {
        return DB::transaction(function () use ($companyId, $attributes, $isOwner, $markEmailVerified): User {
            $userAttributes = [
                'name' => $attributes['user_name'] ?? $attributes['name'] ?? 'Company Admin',
                'email' => $attributes['email'],
                'password' => $attributes['password'],
                'type' => PortalTypeEnum::COMPANY,
            ];

            if ($markEmailVerified) {
                $userAttributes['email_verified_at'] = now();
            }

            $user = $this->users->create($userAttributes);
            $this->companyUsers->create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'is_owner' => $isOwner,
                'is_active' => $attributes['is_active'] ?? true,
            ]);

            if (! $isOwner) {
                $this->syncRoles($user, $companyId, $attributes['roles'] ?? []);
            }

            return $user->load(['companyUser', 'roles']);
        });
    }

    public function updateFromCompanyPortal(int $companyId, User $user, array $attributes): User
    {
        $companyUser = $this->companyUsers->profileFor($companyId, $user);
        $user->loadMissing('roles');
        $this->ensureProtectedAccountCannotBeUpdated($user, $companyUser);

        return DB::transaction(function () use ($companyId, $user, $companyUser, $attributes): User {
            $willBeDeactivated = array_key_exists('is_active', $attributes)
                && ! (bool) $attributes['is_active'];

            $this->users->update($user, collect($attributes)->only(['name', 'email', 'password'])->all());
            $this->updateProfileStatus($companyUser, $attributes);

            if (array_key_exists('roles', $attributes)) {
                $this->syncRoles($user, $companyId, $attributes['roles']);
            }

            if ($willBeDeactivated) {
                $this->accessRevoker->revoke($user);
            }

            return $user->refresh()->load(['companyUser', 'roles']);
        });
    }

    public function updateFromAdminPortal(int $companyId, User $user, array $attributes): User
    {
        $companyUser = $this->companyUsers->profileFor($companyId, $user);
        $user->loadMissing('roles');
        $this->ensureOwnerUpdateIsSafe($user, $companyUser, $attributes);

        return DB::transaction(function () use ($companyId, $user, $companyUser, $attributes): User {
            $previousEmail = $user->email;
            $emailChanged = array_key_exists('email', $attributes) && $attributes['email'] !== $previousEmail;
            $willBeDeactivated = array_key_exists('is_active', $attributes)
                && ! (bool) $attributes['is_active'];

            $this->users->update($user, collect($attributes)->only(['name', 'email'])->all());
            $this->updateProfileStatus($companyUser, $attributes);

            if (array_key_exists('roles', $attributes)) {
                $this->syncRoles($user, $companyId, $attributes['roles']);
            }

            if ($emailChanged || $willBeDeactivated) {
                $this->accessRevoker->revoke($user, [$previousEmail]);
            }

            return $user->refresh()->load(['companyUser', 'roles']);
        });
    }

    public function resetPassword(int $companyId, User $user, string $password): void
    {
        $this->companyUsers->profileFor($companyId, $user);

        DB::transaction(function () use ($user, $password): void {
            $this->users->update($user, ['password' => $password]);
            $this->accessRevoker->revoke($user);
        });
    }

    public function deleteFromCompanyPortal(int $companyId, User $user): void
    {
        $this->delete($companyId, $user, clearRoles: false);
    }

    public function deleteFromAdminPortal(int $companyId, User $user): void
    {
        $this->delete($companyId, $user, clearRoles: true);
    }

    private function delete(int $companyId, User $user, bool $clearRoles): void
    {
        $companyUser = $this->companyUsers->profileFor($companyId, $user);
        $user->loadMissing('roles');
        $this->ensureCompanyUserCanBeDeleted($user, $companyUser);

        DB::transaction(function () use ($user, $companyUser, $clearRoles): void {
            $this->accessRevoker->revoke($user);

            if ($clearRoles) {
                $user->syncRoles([]);
            }

            $this->companyUsers->delete($companyUser);
            $this->users->delete($user);
        });
    }

    private function updateProfileStatus(CompanyUser $companyUser, array $attributes): void
    {
        if (array_key_exists('is_active', $attributes)) {
            $this->companyUsers->update($companyUser, ['is_active' => $attributes['is_active']]);
        }
    }

    /** @throws AuthorizationException */
    private function syncRoles(User $user, int $companyId, array $roleNames): void
    {
        if (in_array(FullAccessRoleProvisioner::COMPANY_OWNER_ROLE, $roleNames, true)) {
            throw new AuthorizationException('The owner role cannot be assigned to managed company users.');
        }

        $user->syncRoles($this->accessControl->scopedRolesByNames(
            PermissionCatalog::COMPANY_PORTAL,
            $companyId,
            $roleNames,
        ));
    }

    /** @throws AuthorizationException */
    private function ensureProtectedAccountCannotBeUpdated(User $user, CompanyUser $companyUser): void
    {
        if ($this->isOwner($user, $companyUser)) {
            throw new AuthorizationException('The company owner account cannot be modified.');
        }
    }

    /** @throws AuthorizationException */
    private function ensureOwnerUpdateIsSafe(User $user, CompanyUser $companyUser, array $attributes): void
    {
        if (! $this->isOwner($user, $companyUser)) {
            return;
        }

        $deactivatesOwner = array_key_exists('is_active', $attributes) && ! (bool) $attributes['is_active'];
        $changesOwnerRoles = array_key_exists('roles', $attributes);

        if ($deactivatesOwner || $changesOwnerRoles) {
            throw new AuthorizationException('The company owner cannot be deactivated or have roles changed.');
        }
    }

    /** @throws AuthorizationException */
    private function ensureCompanyUserCanBeDeleted(User $user, CompanyUser $companyUser): void
    {
        if ($this->isOwner($user, $companyUser)) {
            throw new AuthorizationException('The company owner cannot be deleted.');
        }
    }

    private function isOwner(User $user, CompanyUser $companyUser): bool
    {
        return $companyUser->is_owner
            || $user->roles->contains('name', FullAccessRoleProvisioner::COMPANY_OWNER_ROLE);
    }
}
