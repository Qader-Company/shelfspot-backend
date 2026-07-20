<?php

namespace App\Modules\V1\Users\Application\Profiles;

use App\Modules\V1\Users\Application\Services\UserActivationChecker;
use App\Modules\V1\Users\Application\Services\UserResourceResolver;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class AbstractProfileHandler implements ProfileHandler
{
    abstract protected function portal(): PortalTypeEnum;

    /** @return array<int, string> */
    abstract protected function relations(): array;

    /** @return array<string, array<int, mixed>|string> */
    protected function portalRules(User $user): array
    {
        return [];
    }

    /** @return array<int, string> */
    protected function portalFields(): array
    {
        return [];
    }

    /** @param array<string, mixed> $attributes */
    protected function updatePortalModel(User $user, array $attributes): void {}

    public function rules(User $user): array
    {
        return array_merge([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($user->id)
                    ->where('type', $this->portal()->value),
            ],
            'password' => ['sometimes', 'string', 'confirmed', Password::min(8)->mixedCase()],
            'password_confirmation' => ['required_with:password', 'string'],
        ], $this->portalRules($user));
    }

    public function allowedFields(): array
    {
        return ['name', 'email', 'password', 'password_confirmation', ...$this->portalFields()];
    }

    public function profile(User $user): JsonResource
    {
        $this->assertActive($user);

        return UserResourceResolver::resolve($user->loadMissing($this->relations()), $this->portal());
    }

    public function update(User $user, array $attributes): JsonResource
    {
        $this->assertActive($user);

        DB::transaction(function () use ($user, $attributes) {
            $user->update(Arr::only($attributes, ['name', 'email', 'password']));
            $this->updatePortalModel($user, $attributes);
        });

        return $this->profile($user->refresh());
    }

    private function assertActive(User $user): void
    {
        if ($user->type !== $this->portal() || ! UserActivationChecker::isActive($user, $this->portal())) {
            throw new AccessDeniedHttpException(__('api.forbidden'));
        }
    }
}
