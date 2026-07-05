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

    'portal_profile_rules' => [
        PortalTypeEnum::WORKER->value => [
            'phone' => ['sometimes', 'string', 'max:255'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ],
    ],

    'portal_profile_creation_rules' => [
        PortalTypeEnum::WORKER->value => [
            'phone' => ['required', 'string', 'max:255', 'unique:workers,phone'],
            'latitude' => ['sometimes', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['sometimes', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ],
    ],
];
