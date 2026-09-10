<?php

namespace App\Modules\V1\WorkersWallets\Application\UseCases;

use App\Modules\V1\Tasks\Domain\Models\Task;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Domain\Models\WorkerWalletTransaction;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkerWalletRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\Services\TaskRevenueSplitter;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use LogicException;

class SettleAcceptedTaskUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly WorkerWalletRepositoryInterface $walletRepository,
        private readonly TaskRevenueSplitter $revenueSplitter,
    ) {}

    public function execute(Task $task): WorkerWalletTransaction
    {
        return DB::transaction(function () use ($task) {
            $lockedTask = $this->taskRepository->getByIdAndLockedForUpdate($task->id);

            if (! $lockedTask || $lockedTask->status !== TaskStatusEnum::ACCEPTED) {
                throw new LogicException('Only accepted tasks can be settled.');
            }

            if ($lockedTask->payment_status !== TaskPaymentStatusEnum::CHARGED) {
                throw new LogicException('An accepted task must be charged before settlement.');
            }

            if (! $lockedTask->assigned_worker_id) {
                throw new LogicException('An accepted task must have an assigned worker.');
            }

            $idempotencyKey = 'task_earning:'.$lockedTask->id;
            $existingTransaction = $this->walletRepository->findByIdempotencyKey($idempotencyKey);
            $split = $this->revenueSplitter->split($lockedTask->total_price);

            if ($existingTransaction) {
                if ((int) $existingTransaction->worker_id !== (int) $lockedTask->assigned_worker_id
                    || $existingTransaction->type !== WorkerWalletTransactionTypeEnum::TASK_EARNING
                    || round((float) $existingTransaction->amount, 2) !== round($split['worker_share_amount'], 2)) {
                    throw new LogicException('The existing task earning transaction does not match the settlement.');
                }

                $this->storeSettlementSnapshot($lockedTask, $split, $existingTransaction->created_at);

                return $existingTransaction;
            }

            $worker = Worker::withTrashed()
                ->whereKey($lockedTask->assigned_worker_id)
                ->lockForUpdate()
                ->firstOrFail();

            $transaction = $this->walletRepository->createTransaction(
                worker: $worker,
                type: WorkerWalletTransactionTypeEnum::TASK_EARNING,
                amount: $split['worker_share_amount'],
                idempotencyKey: $idempotencyKey,
                reference: $lockedTask,
                description: __('worker_wallet.task_earning_description', ['task' => $lockedTask->id]),
            );

            $this->storeSettlementSnapshot($lockedTask, $split, $transaction->created_at);

            return $transaction;
        });
    }

    private function storeSettlementSnapshot(Task $task, array $split, mixed $settledAt): void
    {
        $task->forceFill([
            ...$split,
            'settled_at' => $task->settled_at ?? $settledAt ?? now(),
        ])->save();
    }
}
