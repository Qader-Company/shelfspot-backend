<?php

namespace App\Modules\V1\WorkersWallets\Application\UseCases;

use App\Modules\V1\Users\Domain\Models\User;
use App\Modules\V1\WorkersWallets\Domain\Models\WithdrawalRequest;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WithdrawalRequestRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveWithdrawalRequestUseCase
{
    public function __construct(
        private readonly WithdrawalRequestRepositoryInterface $withdrawalRepository,
    ) {}

    public function execute(WithdrawalRequest $withdrawal, User $admin): WithdrawalRequest
    {
        return DB::transaction(function () use ($withdrawal, $admin) {
            $lockedWithdrawal = $this->withdrawalRepository->findForAdmin(
                $withdrawal->id,
                lockForUpdate: true,
            );

            if (! $lockedWithdrawal) {
                throw ValidationException::withMessages([
                    'withdrawal' => __('api.not_found'),
                ]);
            }

            if ($lockedWithdrawal->status === WithdrawalStatusEnum::PAID) {
                return $lockedWithdrawal;
            }

            if ($lockedWithdrawal->status !== WithdrawalStatusEnum::PENDING) {
                throw ValidationException::withMessages([
                    'withdrawal' => __('worker_wallet.pending_withdrawal_only'),
                ]);
            }

            $now = now();
            $lockedWithdrawal->forceFill([
                'status' => WithdrawalStatusEnum::PAID,
                'processed_by' => $admin->id,
                'processed_at' => $now,
                'paid_at' => $now,
                'rejection_reason' => null,
            ])->save();

            return $lockedWithdrawal->refresh()->load(['worker.user', 'processedBy']);
        });
    }
}
