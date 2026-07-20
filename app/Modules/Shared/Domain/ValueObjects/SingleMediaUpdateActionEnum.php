<?php

namespace App\Modules\Shared\Domain\ValueObjects;

enum SingleMediaUpdateActionEnum: string
{
    case KEEP = 'keep';
    case REMOVE = 'remove';
    case REPLACE = 'replace';
}
