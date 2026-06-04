<?php

namespace App\Modules\Shared\Support\Traits;

use Illuminate\Database\Eloquent\Model;

trait DeletesMediaOnForceDelete
{
    protected static function bootDeletesMediaOnForceDelete(): void
    {
        static::forceDeleting(function (Model $model): void {
            $model->media()->get()->each->delete();
        });
    }
}
