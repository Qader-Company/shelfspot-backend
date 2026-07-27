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
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ];
    }

    protected function portalFields(): array
    {
        return ['phone', 'image'];
    }

    protected function updatePortalModel(User $user, array $attributes): void
    {
        $worker = $user->worker;
        $worker->update(Arr::only($attributes, ['phone']));

        if (isset($attributes['image'])) {
            $worker->addMedia($attributes['image'])->toMediaCollection('image');
        }
    }
}
