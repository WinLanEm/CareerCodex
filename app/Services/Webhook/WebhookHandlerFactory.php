<?php

namespace App\Services\Webhook;

use App\Contracts\Services\Webhook\WebhookHandlerFactoryInterface;
use App\Contracts\Services\Webhook\WebhookHandlerInterface;
use App\Enums\ServiceConnectionsEnum;
use App\Services\Webhook\Handlers\AsanaWebhookHandler;
use App\Services\Webhook\Handlers\BitbucketWebhookHandler;
use App\Services\Webhook\Handlers\GithubWebhookHandler;
use App\Services\Webhook\Handlers\GitlabWebhookHandler;
use App\Services\Webhook\Handlers\JiraWebhookHandler;

class WebhookHandlerFactory implements WebhookHandlerFactoryInterface
{
    public function make(ServiceConnectionsEnum $serviceConnectionsEnum):WebhookHandlerInterface
    {
        return match ($serviceConnectionsEnum->value) {
            ServiceConnectionsEnum::GITHUB->value => app(GithubWebhookHandler::class),
            ServiceConnectionsEnum::GITLAB->value => app(GitlabWebhookHandler::class),
            ServiceConnectionsEnum::BITBUCKET->value => app(BitbucketWebhookHandler::class),
            ServiceConnectionsEnum::ASANA->value => app(AsanaWebhookHandler::class),
            ServiceConnectionsEnum::JIRA->value => app(JiraWebhookHandler::class),
            default => throw new \InvalidArgumentException("Webhook handler for service '{$serviceConnectionsEnum->value}' not found."),
        };
    }
}
