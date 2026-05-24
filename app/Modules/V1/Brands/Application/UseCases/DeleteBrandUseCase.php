<?php

namespace App\Modules\V1\Brands\Application\UseCases;

use App\Modules\V1\Brands\Domain\Repositories\BrandRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DeleteBrandUseCase
{

    public function __construct(
        private readonly BrandRepositoryInterface $brandRepository
    )
    {
    }

    public function execute( string $id)
    {
        $brand = $this->brandRepository->getById($id);
        if(is_null($brand))
            throw new ModelNotFoundException(__('brands.not_found'));

        $this->brandRepository->delete($brand);
    }
}
