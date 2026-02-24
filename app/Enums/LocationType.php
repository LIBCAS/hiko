<?php

namespace App\Enums;

enum LocationType: string
{
    case Repository = 'repository';
    case Collection = 'collection';
    case Archive = 'archive';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
