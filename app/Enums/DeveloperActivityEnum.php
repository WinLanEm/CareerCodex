<?php

namespace App\Enums;

enum DeveloperActivityEnum:string
{
    case COMMIT = 'commit';
    case PULL_REQUEST = 'pull_request';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
