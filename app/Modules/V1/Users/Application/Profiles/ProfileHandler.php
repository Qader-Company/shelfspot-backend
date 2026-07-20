<?php

namespace App\Modules\V1\Users\Application\Profiles;

use App\Modules\V1\Users\Domain\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

interface ProfileHandler
{
    /** @return array<string, array<int, mixed>|string> */
    public function rules(User $user): array;

    /** @return array<int, string> */
    public function allowedFields(): array;

    public function profile(User $user): JsonResource;

    public function update(User $user, array $attributes): JsonResource;
}
