<?php

use App\Modules\V1\Users\Domain\ValueObjects\PortalTypeEnum;

return [
    'providers' => [
        'google' => [
            'allowed_portals' => array_filter(array_map(
                'trim',
                explode(',', env('GOOGLE_SOCIAL_AUTH_PORTALS', PortalTypeEnum::WORKER->value))
            )),
        ],
    ],
];
