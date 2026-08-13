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
            'event' => ['required', Rule::in(array_keys(config('notification_lab.events')))],
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
        $payload = $this->payload($validated['event'], $testRunId);

        $users->each(fn (User $user) => $user->notify(new RealtimeNotification(
            $payload,
            "notification-lab:$testRunId:user:$user->id",
        )));

        return response()->json([
            'data' => [
                'test_run_id' => $testRunId,
                'event' => $validated['event'],
                'payload' => $payload,
                'queued' => $users->count(),
                'recipients' => $users->map(fn (User $user) => [
                    'user_id' => $user->id,
                    'portal' => $user->type->value,
                    'status' => 'queued',
                ])->values(),
            ],
        ], 202);
    }

    private function payload(string $event, string $testRunId): array
    {
        $eventDefinition = config('notification_lab.events')[$event];
        $taskId = config('notification_lab.fixtures.task_id');
        $translationKey = 'notifications.'.str_replace('.', '_', $event);

        return [
            'event' => $event,
            'category' => 'task',
            'priority' => $eventDefinition['priority'],
            'title' => __($translationKey.'.title'),
            'description' => __($translationKey.'.description', ['task' => $taskId]),
            'task_id' => $taskId,
            'company_id' => config('notification_lab.fixtures.company_id'),
            'status' => $eventDefinition['status'],
            'actor_id' => null,
            'action' => ['resource' => 'task', 'id' => $taskId],
            'meta' => [
                'is_test' => true,
                'source' => 'notification-lab',
                'test_run_id' => $testRunId,
                'status_history_id' => config('notification_lab.fixtures.status_history_id'),
            ],
            'occurred_at' => now()->toIso8601String(),
        ];
    }
}
