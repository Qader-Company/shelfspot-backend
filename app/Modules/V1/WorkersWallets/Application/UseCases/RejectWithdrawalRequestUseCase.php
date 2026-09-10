<?php

namespace App\Modules\V1\WorkersWallets\Application\UseCases;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Domain\Models\WithdrawalRequest;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WithdrawalRequestRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkerWalletRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalStatusEnum;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RejectWithdrawalRequestUseCase
{
    public function __construct(
        private readonly WithdrawalRequestRepositoryInterface $withdrawalRepository,
        private readonly WorkerWalletRepositoryInterface $walletRepository,
    ) {}

    public function execute(WithdrawalRequest $withdrawal, User $admin, string $reason): WithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawal, $admin, $reason) {
            $lockedWithdrawal = $this->withdrawalRepository->findForAdmin(
                $withdrawal->id,
                lockForUpdate: true,
            );

            if (! $lockedWithdrawal) {
                throw ValidationException::withMessages([
                    'withdrawal' => __('api.not_found'),
                ]);
            }

            if ($lockedWithdrawal->status === WithdrawalStatusEnum::REJECTED) {
                return $lockedWithdrawal;
            }

            if ($lockedWithdrawal->status !== WithdrawalStatusEnum::PENDING) {
                throw ValidationException::withMessages([
                    'withdrawal' => __('worker_wallet.pending_withdrawal_only'),
                ]);
            }

            $worker = Worker::withTrashed()
                ->whereKey($lockedWithdrawal->worker_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->walletRepository->createTransaction(
                worker: $worker,
                type: WorkerWalletTransactionTypeEnum::WITHDRAWAL_REFUND,
                amount: $lockedWithdrawal->amount,
                idempotencyKey: 'withdrawal_refund:'.$lockedWithdrawal->id,
                reference: $lockedWithdrawal,
                description: __('worker_wallet.withdrawal_refund_description', ['withdrawal' => $lockedWithdrawal->id]),
            );

            $lockedWithdrawal->forceFill([
                'status' => WithdrawalStatusEnum::REJECTED,
                'processed_by' => $admin->id,
                'processed_at' => now(),
                'paid_at' => null,
                'rejection_reason' => $reason,
            ])->save();

            return $lockedWithdrawal->refresh()->load(['worker.user', 'processedBy']);
        });
    }
}
