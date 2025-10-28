<?php

namespace App\Services\Webhook\Handlers;

use App\Enums\ServiceConnectionsEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class JiraWebhookHandler extends AbstractWebhookHandler
{
    public function verify(array $payload, string $rawPayload, array $headers,?string $secret): bool
    {
        if(!isset($payload['matchedWebhookIds'][0])){
            return false;
        }
        $webhookId = $payload['matchedWebhookIds'][0];

        $webhook = $this->webhookRepository->find(
            function (Builder $query) use ($webhookId) {
                return $query->where('webhook_id', $webhookId)
                    ->whereHas('integration',function (Builder $query){
                        $query->where('service', ServiceConnectionsEnum::JIRA->value);
                    });
            }
        );

        if(!$webhook){
            return false;
        }
        return hash_equals($webhook->secret,$secret);
    }

    public function handle(array $payload, array $headers): void
    {
        if (($payload['webhookEvent'] ?? null) !== 'jira:issue_updated') {
            return;
        }

        $issue = $payload['issue'] ?? null;
        if (!$issue) {
            return;
        }


        $fields = $issue['fields'] ?? [];

        $userAccountId = $issue['user']['accountId'] ?? $payload['user']['accountId'] ?? null;
        if (!$userAccountId) return;

        $integration = $this->findIntegrationById($userAccountId,ServiceConnectionsEnum::JIRA);
        if (!$integration) return;

        $assigneeId = $fields['assignee']['accountId'] ?? null;
        $reporterId = $fields['reporter']['accountId'] ?? null;

        if ($userAccountId !== $assigneeId && $userAccountId !== $reporterId) {
            return;
        }

        $projectId = $fields['project']['id'] ?? null;
        if (!$projectId) return;

        $issueSelfUrl = $payload['issue']['self'] ?? null;

        $urlParts = parse_url($issueSelfUrl);
        if (!$urlParts || !isset($urlParts['scheme']) || !isset($urlParts['host'])) {
            return;
        }

        $projectName = $fields['project']['key'] ?? null;

        $siteUrl = $urlParts['scheme'] . '://' . $urlParts['host'] . '/browse/' . $projectName;

        $integrationInstance = $this->integrationInstanceByClosureRepository->findIntegrationInstanceByClosure(
            function (Builder $query) use ($siteUrl,$integration) {
                return $query->where('site_url',$siteUrl)
                    ->where('integration_id',$integration->id);
            }
        );


        if (!$integrationInstance) return;

        $descriptionAdf = $payload['issue']['fields']['description'] ?? null;
        $descriptionText = $descriptionAdf ? trim($this->extractTextFromAdf($descriptionAdf)) : '';
        $resolutionDateString = $fields['resolutiondate'];
        $carbonDate = Carbon::parse($resolutionDateString);

        $urlParts = parse_url($issue['self']);
        $baseUrl = $urlParts['scheme'] . '://' . $urlParts['host'];

        $issueKey = $issue['key'];

        $link = $baseUrl . '/browse/' . $issueKey;

        $this->achievementRepository->updateOrCreate([
            'title' => $fields['summary'],
            'description' => $descriptionText,
            'date' => $carbonDate,
            'is_approved' => false,
            'is_from_provider' => true,
            'integration_instance_id' => $integrationInstance->id,
            'project_name' => $fields['project']['name'],
            'link' => $link,
        ]);
    }
    private function extractTextFromAdf(mixed $node): string
    {
        if (!$node) {
            return '';
        }

        if (is_string($node)) {
            return $node;
        }

        if (!is_array($node)) {
            return '';
        }

        $text = '';
        if (($node['type'] ?? null) === 'text' && !empty($node['text'])) {
            $text .= $node['text'];
        }

        if (!empty($node['content'])) {
            foreach ($node['content'] as $childNode) {
                $text .= $this->extractTextFromAdf($childNode);
            }
        }

        if (($node['type'] ?? null) === 'paragraph') {
            if (trim($text) !== '') {
                $text .= PHP_EOL;
            }
        }

        return $text;
    }
}
