<?php

namespace App\Modules\V1\Authentication\Domain\ValueObjects;

enum SocialProviderEnum: string
{
    case GOOGLE = 'google';

    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
