<?php

namespace App\Support\NotificationLab;

use App\Http\Controllers\Controller;
use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use App\Notifications\RealtimeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SendTestNotificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless(config('notification_lab.sending_enabled'), 404);

        $validated = $request->validate([
            'targets' => ['required', 'array', 'min:1', 'max:3'],
            'targets.*.portal' => ['required', Rule::enum(PortalTypeEnum::class)],
            'targets.*.user_id' => ['required', 'integer', 'distinct', 'min:1'],
            'event' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_.-]+$/'],
            'category' => ['required', 'string', 'max:50'],
            'priority' => ['required', Rule::in(['normal', 'high'])],
            'title' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:1000'],
            'action_resource' => ['nullable', 'string', 'max:100'],
            'action_id' => ['nullable', 'integer', 'min:1'],
            'meta' => ['nullable', 'array'],
        ]);

        $targetsById = collect($validated['targets'])->keyBy('user_id');
        $users = User::query()->whereKey($targetsById->keys())->get();

        $errors = [];
        foreach ($validated['targets'] as $index => $target) {
            $user = $users->firstWhere('id', $target['user_id']);

            if (! $user) {
                $errors["targets.$index.user_id"][] = 'The selected user does not exist.';
            } elseif ($user->type->value !== $target['portal']) {
                $errors["targets.$index.portal"][] = "User {$user->id} belongs to the {$user->type->value} portal.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $testRunId = (string) Str::uuid();
        $payload = [
            'event' => $validated['event'],
            'category' => $validated['category'],
            'priority' => $validated['priority'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'action' => [
                'resource' => $validated['action_resource'] ?? null,
                'id' => $validated['action_id'] ?? null,
            ],
            'meta' => array_merge($validated['meta'] ?? [], [
                'is_test' => true,
                'source' => 'notification-lab',
                'test_run_id' => $testRunId,
            ]),
            'occurred_at' => now()->toIso8601String(),
        ];

        $users->each(fn (User $user) => $user->notify(new RealtimeNotification(
            $payload,
            "notification-lab:$testRunId:user:$user->id",
        )));

        return response()->json([
            'data' => [
                'test_run_id' => $testRunId,
                'queued' => $users->count(),
                'recipients' => $users->map(fn (User $user) => [
                    'user_id' => $user->id,
                    'portal' => $user->type->value,
                    'status' => 'queued',
                ])->values(),
            ],
        ], 202);
    }
}
