<?php

namespace App\Modules\Shared\Support\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasTranslation
{
    private function fillTranslations(Model $model, array $translations): void
    {
        foreach ($translations as $locale => $fields) {
            $model->translateOrNew($locale)->fill($fields);
            $model->save();
        }
    }

}
