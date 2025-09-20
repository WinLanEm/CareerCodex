<?php

namespace App\Enums;

enum ServiceConnectionsEnum:string
{
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case BITBUCKET = 'bitbucket';
    case JIRA = 'jira';
    //case TRELLO = 'trello';
    case ASANA = 'asana';

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
