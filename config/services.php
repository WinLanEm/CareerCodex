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
        'graph_ql_url' => 'https://api.github.com/graphql',
        'sync_repositories_url' => 'https://api.github.com/user/repos',
        'get_hooks_url' => "https://api.github.com/repos/{fullRepoName}/hooks",
    ],

    'gitlab_integration' => [
        'scopes' => 'read_api read_user',
        'client_id' => env('GITLAB_SERVICE_CLIENT_ID'),
        'client_secret' => env('GITLAB_SERVICE_CLIENT_SECRET'),
        'redirect' => env('GITLAB_SERVICE_REDIRECT_URI'),
        'sync_repositories_url' => 'https://gitlab.com/api/v4/projects',
        'get_merged_pull_requests_url' => "https://gitlab.com/api/v4/projects/{projectId}/merge_requests",
        'get_commits_url' => "https://gitlab.com/api/v4/projects/{projectId}/repository/commits",
        'get_hooks_url' => "https://gitlab.com/api/v4/projects/{projectId}/hooks",
    ],

    'bitbucket_integration' => [
        'client_id' => env('BITBUCKET_SERVICE_CLIENT_ID'),
        'client_secret' => env('BITBUCKET_SERVICE_CLIENT_SECRET'),
        'redirect' => env('BITBUCKET_SERVICE_REDIRECT_URI'),
        'sync_repositories_url' => 'https://api.bitbucket.org/2.0/repositories?role=member',
        'get_merged_pull_requests_url' => "https://api.bitbucket.org/2.0/repositories/{workspaceSlug}/{repoSlug}/pullrequests",
        'get_commits_url' => "https://api.bitbucket.org/2.0/repositories/{workspaceSlug}/{repoSlug}/commits",
        'get_hooks_url' => "https://api.bitbucket.org/2.0/repositories/{workspace}/{repoSlug}/hooks",
    ],
    'jira_integration' => [
        'scopes' => 'read:jira-user read:jira-work offline_access read:me manage:jira-webhook',
        'client_id' => env('JIRA_SERVICE_CLIENT_ID'),
        'client_secret' => env('JIRA_SERVICE_CLIENT_SECRET'),
        'redirect' => env('JIRA_SERVICE_REDIRECT_URI'),
        'provider_instance_url' => 'https://api.atlassian.com/oauth/token/accessible-resources',
        'projects_url' => "https://api.atlassian.com/ex/jira/{cloudId}/rest/api/3/project/search",
        'sync_issue' => "https://api.atlassian.com/ex/jira/{cloudId}/rest/api/3/search",
    ],

    'asana_integration' => [
        'scopes' => 'tasks:read projects:read users:read stories:read workspaces:read',
        'client_id' => env('ASANA_SERVICE_CLIENT_ID'),
        'client_secret' => env('ASANA_SERVICE_CLIENT_SECRET'),
        'redirect' => env('ASANA_SERVICE_REDIRECT_URI'),
        'provider_instance_url' => 'https://app.asana.com/api/1.0/workspaces',
        'projects_url' => "https://app.asana.com/api/1.0/projects",
        'sync_issue' => "https://app.asana.com/api/1.0/tasks",
    ],

    'trello_integration' => [
        'client_id' => env('TRELLO_SERVICE_CLIENT_ID'),
        'client_secret' => env('TRELLO_SERVICE_CLIENT_SECRET'),
        'redirect' => env('TRELLO_SERVICE_REDIRECT_URI'),
    ],
];
