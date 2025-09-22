<?php

namespace App\Providers;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementCreateRepositoryInterface;
use App\Contracts\Repositories\Achievement\WorkspaceAchievementDeleteRepositoryInterface;
use App\Contracts\Repositories\Achievement\WorkspaceAchievementFindRepositoryInterface;
use App\Contracts\Repositories\Achievement\WorkspaceAchievementIndexRepositoryInterface;
use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Contracts\Repositories\Email\GenerateVerificationCodeRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\Integrations\UpdateOrCreateIntegrationRepositoryInterface;
use App\Contracts\Repositories\User\CreateUserRepositoryInterface;
use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Contracts\Repositories\User\UpdateOrCreateUserRepositoryInterface;
use App\Contracts\Repositories\User\UpdateUserRepositoryInterface;
use App\Contracts\Repositories\Workspace\CreateWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\DeleteWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\IndexWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\UpdateWorkspaceRepositoryInterface;
use App\Contracts\Services\HttpServices\AsanaApiServiceInterface;
use App\Contracts\Services\HttpServices\BitbucketApiServiceInterface;
use App\Contracts\Services\HttpServices\GithubApiServiceInterface;
use App\Contracts\Services\HttpServices\GithubRepositoryActivityFetcherInterface;
use App\Contracts\Services\HttpServices\GithubRepositoryListFetcherInterface;
use App\Contracts\Services\HttpServices\GitlabApiServiceInterface;
use App\Contracts\Services\HttpServices\JiraApiServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Jobs\SyncDeveloperActivities\SyncGitBaseJob;
use App\Jobs\SyncDeveloperActivities\SyncGithubJob;
use App\Jobs\SyncDeveloperRepositories\SyncGithubRepositoryJob;
use App\Jobs\SyncDeveloperRepositories\SyncRepositoryBaseJob;
use App\Models\Integration;
use App\Observers\ServiceConnectionObserver;
use App\Repositories\Achievement\WorkspaceAchievementCreateRepository;
use App\Repositories\Achievement\WorkspaceAchievementDeleteRepository;
use App\Repositories\Achievement\WorkspaceAchievementFindRepository;
use App\Repositories\Achievement\WorkspaceAchievementIndexRepository;
use App\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepository;
use App\Repositories\Achievement\WorkspaceAchievementUpdateRepository;
use App\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivity;
use App\Repositories\Email\GenerateVerificationCodeRepository;
use App\Repositories\IntegrationInstances\UpdateOrCreateIntegrationInstanceRepository;
use App\Repositories\Integrations\UpdateOrCreateIntegrationRepository;
use App\Repositories\Integrations\UpdateIntegrationRepository;
use App\Repositories\User\CreateUserRepository;
use App\Repositories\User\FindUserRepository;
use App\Repositories\User\UpdateOrCreateUserRepository;
use App\Repositories\User\UpdateUserRepository;
use App\Repositories\Workspace\CreateWorkspaceRepository;
use App\Repositories\Workspace\DeleteWorkspaceRepository;
use App\Repositories\Workspace\FindWorkspaceRepository;
use App\Repositories\Workspace\IndexWorkspaceRepository;
use App\Repositories\Workspace\UpdateWorkspaceRepository;
use App\Services\HttpServices\AsanaApiService;
use App\Services\HttpServices\BitbucketApiService;
use App\Services\HttpServices\GithubApiService;
use App\Services\HttpServices\GitlabApiService;
use App\Services\HttpServices\JiraApiService;
use App\Services\HttpServices\ThrottleService;
use App\Services\IntegrationInstanceStrategy\GetIntegrationInstanceStrategy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\BitbucketProvider;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\GitlabProvider;
use SocialiteProviders\Asana\Provider as AsanaProvider;
use SocialiteProviders\Atlassian\Provider as AtlassianProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            GenerateVerificationCodeRepositoryInterface::class,
            GenerateVerificationCodeRepository::class
        );
        $this->app->bind(
            CreateUserRepositoryInterface::class,
            CreateUserRepository::class
        );
        $this->app->bind(
            FindUserRepositoryInterface::class,
            FindUserRepository::class
        );
        $this->app->bind(
            IndexWorkspaceRepositoryInterface::class,
            IndexWorkspaceRepository::class
        );
        $this->app->bind(
            CreateWorkspaceRepositoryInterface::class,
            CreateWorkspaceRepository::class
        );
        $this->app->bind(
            DeleteWorkspaceRepositoryInterface::class,
            DeleteWorkspaceRepository::class
        );
        $this->app->bind(
            FindWorkspaceRepositoryInterface::class,
            FindWorkspaceRepository::class
        );
        $this->app->bind(
            UpdateWorkspaceRepositoryInterface::class,
            UpdateWorkspaceRepository::class
        );
        $this->app->bind(
            WorkspaceAchievementIndexRepositoryInterface::class,
            WorkspaceAchievementIndexRepository::class
        );
        $this->app->bind(
            WorkspaceAchievementCreateRepositoryInterface::class,
            WorkspaceAchievementCreateRepository::class
        );
        $this->app->bind(
            WorkspaceAchievementFindRepositoryInterface::class,
            WorkspaceAchievementFindRepository::class
        );
        $this->app->bind(
            WorkspaceAchievementUpdateRepositoryInterface::class,
            WorkspaceAchievementUpdateRepository::class
        );
        $this->app->bind(
            WorkspaceAchievementDeleteRepositoryInterface::class,
            WorkspaceAchievementDeleteRepository::class
        );
        $this->app->bind(
            UpdateOrCreateUserRepositoryInterface::class,
            UpdateOrCreateUserRepository::class
        );
        $this->app->bind(
            UpdateUserRepositoryInterface::class,
            UpdateUserRepository::class
        );
        $this->app->bind(
            UpdateOrCreateIntegrationRepositoryInterface::class,
            UpdateOrCreateIntegrationRepository::class
        );
        $this->app->bind(
            GetIntegrationInstanceStrategyInterface::class,
            GetIntegrationInstanceStrategy::class
        );
        $this->app->bind(
            UpdateOrCreateIntegrationInstanceRepositoryInterface::class,
            UpdateOrCreateIntegrationInstanceRepository::class
        );
        $this->app->bind(
            WorkspaceAchievementUpdateOrCreateRepositoryInterface::class,
            WorkspaceAchievementUpdateOrCreateRepository::class
        );
        $this->app->bind(
            UpdateIntegrationInstanceRepositoryInterface::class,
            UpdateIntegrationRepository::class
        );;
        $this->app->bind(
            UpdateOrCreateDeveloperActivityInterface::class,
            UpdateOrCreateDeveloperActivity::class
        );
        $this->app->bind(
            GithubApiServiceInterface::class,
            GithubApiService::class
        );
        $this->app->bind(
            GitlabApiServiceInterface::class,
            GitlabApiService::class
        );
        $this->app->bind(
            BitbucketApiServiceInterface::class,
            BitbucketApiService::class
        );
        $this->app->bind(
            JiraApiServiceInterface::class,
            JiraApiService::class
        );
        $this->app->bind(
            AsanaApiServiceInterface::class,
            AsanaApiService::class
        );
        $this->app->bind(
            ThrottleServiceInterface::class,
            ThrottleService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Socialite::extend('github_integration', function ($app) {
            $config = $app['config']['services.github_integration'];
            return Socialite::buildProvider(GithubProvider::class, $config);
        });
        Socialite::extend('gitlab_integration', function ($app) {
            $config = $app['config']['services.gitlab_integration'];
            return Socialite::buildProvider(GitlabProvider::class, $config);
        });
        Socialite::extend('bitbucket_integration', function ($app) {
            $config = $app['config']['services.bitbucket_integration'];
            return Socialite::buildProvider(BitbucketProvider::class, $config);
        });
        Socialite::extend('jira_integration', function ($app) {
            $config = $app['config']['services.jira_integration'];
            return Socialite::buildProvider(AtlassianProvider::class, $config);
        });

        Socialite::extend('trello_integration', function ($app) {
            $config = $app['config']['services.trello_integration'];
            return Socialite::buildProvider(AtlassianProvider::class, $config);
        });

        Socialite::extend('asana_integration', function ($app) {
            $config = $app['config']['services.asana_integration'];
            return Socialite::buildProvider(AsanaProvider::class, $config);
        });

    }
}
