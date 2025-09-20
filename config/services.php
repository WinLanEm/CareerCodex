<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'github' => [
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'redirect' => env('GITHUB_REDIRECT_URI'),
    ],

    'gitlab' => [
        'client_id' => env('GITLAB_CLIENT_ID'),
        'client_secret' => env('GITLAB_CLIENT_SECRET'),
        'redirect' => env('GITLAB_REDIRECT_URI'),
    ],

    'bitbucket' => [
        'client_id' => env('BITBUCKET_CLIENT_ID'),
        'client_secret' => env('BITBUCKET_CLIENT_SECRET'),
        'redirect' => env('BITBUCKET_REDIRECT_URI'),
    ],

    'github_integration' => [
        'client_id' => env('GITHUB_SERVICE_CLIENT_ID'),
        'client_secret' => env('GITHUB_SERVICE_CLIENT_SECRET'),
        'redirect' => env('GITHUB_SERVICE_REDIRECT_URI'),
    ],

    'gitlab_integration' => [
        'scopes' => 'read_api read_user',
        'client_id' => env('GITLAB_SERVICE_CLIENT_ID'),
        'client_secret' => env('GITLAB_SERVICE_CLIENT_SECRET'),
        'redirect' => env('GITLAB_SERVICE_REDIRECT_URI'),
    ],

    'bitbucket_integration' => [
        'client_id' => env('BITBUCKET_SERVICE_CLIENT_ID'),
        'client_secret' => env('BITBUCKET_SERVICE_CLIENT_SECRET'),
        'redirect' => env('BITBUCKET_SERVICE_REDIRECT_URI'),
    ],
    'jira_integration' => [
        'scopes' => 'read:jira-user read:jira-work offline_access read:me manage:jira-webhook',
        'client_id' => env('JIRA_SERVICE_CLIENT_ID'),
        'client_secret' => env('JIRA_SERVICE_CLIENT_SECRET'),
        'redirect' => env('JIRA_SERVICE_REDIRECT_URI'),
        'provider_instance_url' => 'https://api.atlassian.com/oauth/token/accessible-resources'
    ],

    'asana_integration' => [
        'scopes' => 'tasks:read projects:read users:read stories:read workspaces:read',
        'client_id' => env('ASANA_SERVICE_CLIENT_ID'),
        'client_secret' => env('ASANA_SERVICE_CLIENT_SECRET'),
        'redirect' => env('ASANA_SERVICE_REDIRECT_URI'),
        'provider_instance_url' => 'https://app.asana.com/api/1.0/workspaces'
    ],

    'trello_integration' => [
        'client_id' => env('TRELLO_SERVICE_CLIENT_ID'),
        'client_secret' => env('TRELLO_SERVICE_CLIENT_SECRET'),
        'redirect' => env('TRELLO_SERVICE_REDIRECT_URI'),
    ],
];
