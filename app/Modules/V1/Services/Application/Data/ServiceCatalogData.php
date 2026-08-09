<?php

namespace App\Modules\V1\Services\Application\Data;

use App\Modules\V1\Services\Domain\Models\Service;

final class ServiceCatalogData
{
    /**
     * @return array<string, mixed>
     */
    public static function from(Service $service): array
    {
        return [
            'id' => $service->id,
            'key' => $service->key->value,
            'name' => $service->key->label(),
            'description' => $service->description,
            'price' => $service->price,
            'is_active' => (bool) $service->is_active,
            'request_form' => $service->key->requestForm(),
            'product_details_form' => $service->key->productDetailsForm(),
            'submission_form' => $service->key->submissionForm(),
        ];
    }
}
