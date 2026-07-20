<?php

namespace App\Modules\V1\Users\Application\Profiles;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class WorkerProfileHandler extends AbstractProfileHandler
{
    protected function portal(): PortalTypeEnum
    {
        return PortalTypeEnum::WORKER;
    }

    protected function relations(): array
    {
        return ['worker.priorityTasks.currentWorkerAssignment'];
    }

    protected function portalRules(User $user): array
    {
        return [
            'phone' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('workers', 'phone')->ignore($user->worker?->id),
            ],
        ];
    }

    protected function portalFields(): array
    {
        return ['phone'];
    }

    protected function updatePortalModel(User $user, array $attributes): void
    {
        $user->worker->update(Arr::only($attributes, $this->portalFields()));
    }
}
