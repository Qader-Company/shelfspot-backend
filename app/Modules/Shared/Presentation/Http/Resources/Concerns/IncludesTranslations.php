<?php

namespace App\Modules\Shared\Presentation\Http\Resources\Concerns;

use Illuminate\Http\Request;

trait IncludesTranslations
{
    protected function translationsWhenShowing(Request $request): mixed
    {
        return $this->when(
            $request->route('id') !== null && $this->resource->relationLoaded('translations'),
            fn () => $this->resource->translations->mapWithKeys(
                fn ($translation) => [
                    $translation->locale => collect($this->resource->translatedAttributes)
                        ->mapWithKeys(fn (string $attribute): array => [
                            $attribute => $translation->{$attribute},
                        ])
                        ->all(),
                ]
            )
        );
    }
}
