<?php

namespace App\Modules\V1\WorkersWallets\Application\UseCases;

use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Domain\Models\WithdrawalRequest;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WithdrawalRequestRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkerWalletRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalMethodEnum;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WithdrawalStatusEnum;
use App\Modules\V1\WorkersWallets\Domain\ValueObjects\WorkerWalletTransactionTypeEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateWithdrawalRequestUseCase
{
    public function __construct(
        private readonly WithdrawalRequestRepositoryInterface $withdrawalRepository,
        private readonly WorkerWalletRepositoryInterface $walletRepository,
    ) {}

    public function execute(Worker $worker, array $attributes): WithdrawalRequest
    {
        return DB::transaction(function () use ($worker, $attributes) {
            $lockedWorker = Worker::query()->whereKey($worker->id)->lockForUpdate()->firstOrFail();

            if (! $lockedWorker->is_active) {
                throw ValidationException::withMessages([
                    'worker' => __('api.forbidden'),
                ]);
            }

            $method = WithdrawalMethodEnum::from($attributes['method']);
            $withdrawal = $this->withdrawalRepository->create([
                'worker_id' => $lockedWorker->id,
                'amount' => $attributes['amount'],
                'method' => $method,
                'status' => WithdrawalStatusEnum::PENDING,
                'payout_details' => $this->payoutDetails($method, $attributes),
            ]);

            try {
                $this->walletRepository->createTransaction(
                    worker: $lockedWorker,
                    type: WorkerWalletTransactionTypeEnum::WITHDRAWAL,
                    amount: $withdrawal->amount,
                    idempotencyKey: 'withdrawal:'.$withdrawal->id,
                    reference: $withdrawal,
                    description: __('worker_wallet.withdrawal_description', ['withdrawal' => $withdrawal->id]),
                );
            } catch (InvalidArgumentException $exception) {
                throw ValidationException::withMessages([
                    'amount' => $exception->getMessage(),
                ]);
            }

            return $withdrawal->refresh();
        });
    }

    private function payoutDetails(WithdrawalMethodEnum $method, array $attributes): array
    {
        return match ($method) {
            WithdrawalMethodEnum::BANK_ACCOUNT => [
                'iban' => $attributes['iban'],
            ],
            WithdrawalMethodEnum::WALLET => [
                'wallet_number' => $attributes['wallet_number'],
                'wallet_provider' => $attributes['wallet_provider'] ?? null,
            ],
        };
    }
}
