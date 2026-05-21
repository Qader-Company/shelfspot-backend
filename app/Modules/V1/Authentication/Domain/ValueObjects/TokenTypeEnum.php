<?php

namespace App\Modules\V1\Authentication\Domain\ValueObjects;
enum TokenTypeEnum: string
{
    case ACCESS_TOKEN = "access";
    case REFRESH_TOKEN = "refresh";
    case VERIFY_TOKEN = "verification";
    case RESET_PASSWORD_TOKEN = "reset_password";
}
