<?php

namespace App\Modules\V1\Companies\Domain\ValueObjects;

enum CompanyIndustryEnum: string
{
    case INDUSTRY_ONE = 'industry_one';
    case INDUSTRY_TWO = 'industry_two';
    case INDUSTRY_THREE = 'industry_three';
    case INDUSTRY_Four = 'industry_four';


    public static function values() : array
    {
        return array_map(
            fn(self $item) => $item->value,
            self::cases()
        );
    }
}
