<?php

namespace App\Modules\V1\Brands\Application\UseCases;

use App\Modules\V1\Brands\Domain\Repositories\BrandRepositoryInterface;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

class UpdateBrandUseCase
{

    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository
    )
    {
    }

    public function execute( string $id, array $attributes): void
    {
        $brand = $this->brandRepository->getById($id);
        if(is_null($brand))
            throw new NotFoundResourceException(__('brands.not_found'));

        $this->brandRepository->update(
            $brand,
            $attributes,
            logo: $attributes['logo'] ?? null
        );
    }
}
