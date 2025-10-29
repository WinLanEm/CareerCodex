<?php

namespace App\Actions\Github;



use App\Contracts\Services\HttpServices\Github\GithubCheckIfAppInstalledInterface;
use App\Http\Resources\UrlResource;
use Laravel\Socialite\Contracts\User;

readonly class CheckGitHubAppInstallation
{
    public function __construct(private GithubCheckIfAppInstalledInterface $appInstalled)
    {
    }

    public function __invoke(User $providerUser): ?UrlResource
    {
        if (!$this->appInstalled->checkIfAppIsInstalled($providerUser)) {
            $appSlug = config('services.github_integration.app_slug');
            $installationUrl = "https://github.com/apps/{$appSlug}/installations/new";

            return new UrlResource($installationUrl, false, 403);
        }

        return null;
    }
}
