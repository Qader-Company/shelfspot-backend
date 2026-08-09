<?php

namespace App\Modules\V1\PlatformSettings\Application\Data;

use App\Modules\V1\PlatformSettings\Domain\Models\PlatformSetting;

final class PlatformSettingsData
{
    /**
     * @return array{email: ?string, phone: ?string, address: ?string, description_ar: ?string, description_en: ?string}
     */
    public static function from(PlatformSetting $settings): array
    {
        return [
            'email' => $settings->email,
            'phone' => $settings->phone,
            'address' => $settings->address,
            'description_ar' => $settings->description_ar,
            'description_en' => $settings->description_en,
        ];
    }
}
