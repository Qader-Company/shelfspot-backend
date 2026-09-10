<?php

namespace App\Modules\V1\WorkersWallets\Application\UseCases;

use App\Modules\V1\Workers\Domain\Models\Worker;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WithdrawalRequestRepositoryInterface;
use App\Modules\V1\WorkersWallets\Domain\Repositories\WorkerWalletRepositoryInterface;

class GetWorkerWalletOverviewUseCase
{
    public function __construct(
        private readonly WorkerWalletRepositoryInterface $walletRepository,
        private readonly WithdrawalRequestRepositoryInterface $withdrawalRepository,
    ) {}

    public function execute(Worker $worker): array
    {
        return [
            'available_balance' => $this->walletRepository->currentBalance($worker->id),
            'total_earned' => $this->walletRepository->totalEarned($worker->id),
            ...$this->withdrawalRepository->workerSummary($worker->id),
            'latest_transactions' => $this->walletRepository->latestTransactions($worker->id),
        ];
    }
}
