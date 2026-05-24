<?php

namespace App\Modules\V1\Companies\Application\UseCases;

use App\Modules\V1\Companies\Domain\Repositories\CompanyRepositoryInterface;
use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;
use Illuminate\Support\Str;

class CreateCompanyUseCase
{
    public function __construct(
        private CompanyRepositoryInterface $companyRepository,
    )
    {
    }

    public function execute(array $attributes)
    {
        $slug = $attributes['slug'] ?? $this->generateUniqueCompanySlug($attributes['name']);

        $company = $this->companyRepository->create([
                'name' => $attributes['name'],
                'slug' => $slug,
                'email' => $attributes['email'],
                'phone' => $attributes['phone'],
                'cr_number' => $attributes['cr_number'],
                'industry' => $attributes['industry'],
                'type' => PortalTypeEnum::COMPANY
            ]);
    }

    private function generateUniqueCompanySlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'company';

        $slug = $baseSlug;
        $counter = 1;

        while ($this->companyRepository->exists(['slug' => $slug])) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
