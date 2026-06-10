<?php

namespace App\Modules\V1\Services\Presentation\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    public function __construct($resource, protected bool $withTranslations = false)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key->value,
            'name' => $this->key->label(),
            'description' => $this->description,
            'minimum_price' => $this->minimum_price,
            'minimum_execution_time' => $this->minimum_execution_time,
            'is_active' => (bool) $this->is_active,
            'request_form' => $this->key->requestForm(),
            'submit_form' => $this->key->submitForm(),
            'submission_form' => $this->key->submissionForm(),
            'translations' => $this->when(
                $this->withTranslations,
                fn () => $this->translations->mapWithKeys(fn ($t) => [
                    $t->locale => [
                        'description' => $t->description,
                    ],
                ])
            ),
        ];
    }

    public static function withTranslations($resource): self
    {
        return new self($resource, true);
    }

}
