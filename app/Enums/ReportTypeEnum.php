<?php

namespace App\Enums;

enum ReportTypeEnum:string
{
    case DEVELOPER_ACTIVITY = 'developer_activity';
    case ACHIEVEMENT = 'achievement';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
