<?php

namespace App\Enums;

enum IdentityType: string
{
    case Person = 'person';
    case Institution = 'institution';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
