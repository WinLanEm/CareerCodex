<?php

namespace App\Enums;

enum AuthServiceEnum:string
{
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case BITBUCKET = 'bitbucket';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
