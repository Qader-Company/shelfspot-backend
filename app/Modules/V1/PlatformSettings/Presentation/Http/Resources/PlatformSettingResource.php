<?php

namespace App\Modules\V1\PlatformSettings\Presentation\Http\Resources;

use App\Modules\V1\PlatformSettings\Application\Data\PlatformSettingsData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlatformSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return PlatformSettingsData::from($this->resource);
    }
}
