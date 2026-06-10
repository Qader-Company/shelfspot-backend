<?php

namespace App\Modules\V1\Workers\Presentation\Http\Requests;

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateWorkerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $worker = $this->route('worker');
        $workerId = is_object($worker) ? $worker->id : $worker;
        $userId = $this->user()?->id;

        if ($workerId !== null) {
            $userId = Worker::query()->whereKey($workerId)->value('user_id') ?? $userId;
        }

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->ignore($userId)
                    ->where('type', PortalTypeEnum::WORKER->value),
            ],
            'phone' => ['sometimes', 'string', 'max:255', Rule::unique('workers', 'phone')->ignore($workerId)],
            'password' => ['sometimes', 'string', 'confirmed', Password::min(8)->mixedCase()],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
