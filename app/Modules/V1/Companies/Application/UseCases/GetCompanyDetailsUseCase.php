<?php

namespace App\Modules\V1\Companies\Application\UseCases;

use App\Modules\V1\Companies\Domain\Models\Company;
use App\Modules\V1\Products\Domain\Repositories\ProductRepositoryInterface;
use App\Modules\V1\Tasks\Domain\Repositories\TaskRepositoryInterface;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskPaymentStatusEnum;
use App\Modules\V1\Tasks\Domain\ValueObjects\TaskStatusEnum;

class GetCompanyDetailsUseCase
{
    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function execute(Company $company, array $statisticsFilters = []): Company
    {
        $company->setRelation(
            'latestTasks',
            $this->taskRepository->latestForCompany(
                companyId: $company->id,
                limit: 15,
                relations: $this->taskRepository->relations(),
            )
        );

        $company->setAttribute(
            'total_requests_count',
            $this->taskRepository->countForCompany(
                $company->id,
                filters: $statisticsFilters,
            )
        );
        $company->setAttribute(
            'completed_requests_count',
            $this->taskRepository->countForCompany(
                $company->id,
                TaskStatusEnum::COMPLETED,
                $statisticsFilters,
            )
        );
        $company->setAttribute(
            'pending_requests_count',
            $this->taskRepository->countForCompany(
                $company->id,
                TaskStatusEnum::PENDING,
                $statisticsFilters,
            )
        );
        $company->setAttribute(
            'total_spending',
            $this->taskRepository->sumTotalPriceForCompany(
                companyId: $company->id,
                paymentStatus: TaskPaymentStatusEnum::CHARGED,
                filters: $statisticsFilters,
            )
        );
        $company->setAttribute(
            'total_products_count',
            $this->productRepository->countForCompany($company->id, $statisticsFilters)
        );

        return $company;
    }
}
