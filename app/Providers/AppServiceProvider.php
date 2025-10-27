<?php

namespace App\Providers;

use App\Contracts\Repositories\Achievement\AchievementIndexRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementCreateRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementDeleteRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementFindRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementIsApprovedUpdateRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementUpdateOrCreateRepositoryInterface;
use App\Contracts\Repositories\Achievement\AchievementUpdateRepositoryInterface;
use App\Contracts\Repositories\AllActivities\ActivitiesIndexRepositoryInterface;
use App\Contracts\Repositories\AllActivities\ActivitiesPendingApprovalInterface;
use App\Contracts\Repositories\AllActivities\ActivitiesStatRepositoryInterface;
use App\Contracts\Repositories\AllActivities\RecentActivityRepositoryInterface;
use App\Contracts\Repositories\Cache\CacheRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityCreateRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityDeleteRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityFindRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIndexRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIsApprovedUpdateRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityUpdateRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityWithIntegrationDataRepositoryInterface;
use App\Contracts\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivityInterface;
use App\Contracts\Repositories\Email\GenerateVerificationCodeRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\FindIntegrationInstanceByClosureRepositoryInterface;
use App\Contracts\Repositories\IntegrationInstance\UpdateOrCreateIntegrationInstanceRepositoryInterface;
use App\Contracts\Repositories\Integrations\FindIntegrationByClosureRepositoryInterface;
use App\Contracts\Repositories\Integrations\UpdateIntegrationRepositoryInterface;
use App\Contracts\Repositories\Integrations\UpdateOrCreateIntegrationRepositoryInterface;
use App\Contracts\Repositories\User\CreateUserRepositoryInterface;
use App\Contracts\Repositories\User\FindUserRepositoryInterface;
use App\Contracts\Repositories\User\UpdateOrCreateUserRepositoryInterface;
use App\Contracts\Repositories\User\UpdateUserRepositoryInterface;
use App\Contracts\Repositories\Webhook\EloquentWebhookRepositoryInterface;
use App\Contracts\Repositories\Webhook\UpdateOrCreateWebhookRepositoryInterface;
use App\Contracts\Repositories\Workspace\CreateWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\DeleteWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\FindWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\IndexWorkspaceRepositoryInterface;
use App\Contracts\Repositories\Workspace\UpdateWorkspaceRepositoryInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaProjectServiceInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Asana\AsanaWorkspaceServiceInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketActivityFetchInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Bitbucket\BitbucketRepositorySyncInterface;
use App\Contracts\Services\HttpServices\Github\GithubActivityFetchInterface;
use App\Contracts\Services\HttpServices\Github\GithubCheckIfAppInstalledInterface;
use App\Contracts\Services\HttpServices\Github\GithubRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Github\GithubRepositorySyncInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabActivityFetchInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Gitlab\GitlabRepositorySyncInterface;
use App\Contracts\Services\HttpServices\Jira\JiraProjectServiceInterface;
use App\Contracts\Services\HttpServices\Jira\JiraRegisterWebhookInterface;
use App\Contracts\Services\HttpServices\Jira\JiraWorkspaceServiceInterface;
use App\Contracts\Services\HttpServices\ThrottleServiceInterface;
use App\Contracts\Services\ProviderInstanceStrategy\GetIntegrationInstanceStrategyInterface;
use App\Contracts\Services\Report\DownloadReportStrategyInterface;
use App\Contracts\Services\Webhook\WebhookHandlerFactoryInterface;
use App\Repositories\Achievement\AchievementIndexRepository;
use App\Repositories\Achievement\AchievementCreateRepository;
use App\Repositories\Achievement\AchievementDeleteRepository;
use App\Repositories\Achievement\AchievementFindRepository;
use App\Repositories\Achievement\AchievementIsApprovedUpdateRepository;
use App\Repositories\Achievement\AchievementUpdateOrCreateRepository;
use App\Repositories\Achievement\AchievementUpdateRepository;
use App\Repositories\AllActivities\ActivitiesIndexRepository;
use App\Repositories\AllActivities\ActivitiesPendingApprovalRepository;
use App\Repositories\AllActivities\ActivitiesStatRepository;
use App\Repositories\AllActivities\RecentActivityRepository;
use App\Repositories\Cache\CacheRepository;
use App\Repositories\DeveloperActivities\DeveloperActivityCreateRepository;
use App\Repositories\DeveloperActivities\DeveloperActivityDeleteRepository;
use App\Repositories\DeveloperActivities\DeveloperActivityFindRepository;
use App\Repositories\DeveloperActivities\DeveloperActivityIndexRepository;
use App\Repositories\DeveloperActivities\DeveloperActivityIsApprovedUpdateRepository;
use App\Repositories\DeveloperActivities\DeveloperActivityUpdateRepository;
use App\Repositories\DeveloperActivities\DeveloperActivityWithIntegrationDataRepository;
use App\Repositories\DeveloperActivities\UpdateOrCreateDeveloperActivity;
use App\Repositories\Email\GenerateVerificationCodeRepository;
use App\Repositories\IntegrationInstances\FindIntegrationInstanceByClosureRepository;
use App\Repositories\IntegrationInstances\UpdateOrCreateIntegrationInstanceRepository;
use App\Repositories\Integrations\FindIntegrationByClosureRepository;
use App\Repositories\Integrations\UpdateIntegrationRepository;
use App\Repositories\Integrations\UpdateOrCreateIntegrationRepository;
use App\Repositories\User\CreateUserRepository;
use App\Repositories\User\FindUserRepository;
use App\Repositories\User\UpdateOrCreateUserRepository;
use App\Repositories\User\UpdateUserRepository;
use App\Repositories\Webhook\EloquentWebhookRepository;
use App\Repositories\Webhook\UpdateOrCreateWebhookRepository;
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
use App\Services\Report\DownloadReportStrategy;
use App\Services\Webhook\WebhookHandlerFactory;
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
            AchievementCreateRepositoryInterface::class,
            AchievementCreateRepository::class
        );
        $this->app->bind(
            AchievementFindRepositoryInterface::class,
            AchievementFindRepository::class
        );
        $this->app->bind(
            AchievementUpdateRepositoryInterface::class,
            AchievementUpdateRepository::class
        );
        $this->app->bind(
            AchievementDeleteRepositoryInterface::class,
            AchievementDeleteRepository::class
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
            AchievementUpdateOrCreateRepositoryInterface::class,
            AchievementUpdateOrCreateRepository::class
        );
        $this->app->bind(
            UpdateIntegrationRepositoryInterface::class,
            UpdateIntegrationRepository::class
        );;
        $this->app->bind(
            UpdateOrCreateDeveloperActivityInterface::class,
            UpdateOrCreateDeveloperActivity::class
        );
        $this->app->bind(
            BitbucketRepositorySyncInterface::class,
            BitbucketApiService::class
        );

        $this->app->bind(
            BitbucketActivityFetchInterface::class,
            BitbucketApiService::class
        );

        $this->app->bind(
            BitbucketRegisterWebhookInterface::class,
            BitbucketApiService::class
        );
        $this->app->bind(
            GitlabActivityFetchInterface::class,
            GitlabApiService::class
        );
        $this->app->bind(
            GitlabRegisterWebhookInterface::class,
            GitlabApiService::class
        );
        $this->app->bind(
            GitlabRepositorySyncInterface::class,
            GitlabApiService::class
        );
        $this->app->bind(
            GithubActivityFetchInterface::class,
            GithubApiService::class
        );
        $this->app->bind(
            GithubRepositorySyncInterface::class,
            GithubApiService::class
        );
        $this->app->bind(
            GithubRegisterWebhookInterface::class,
            GithubApiService::class
        );
        $this->app->bind(
            GithubCheckIfAppInstalledInterface::class,
            GithubApiService::class
        );
        $this->app->bind(
            JiraWorkspaceServiceInterface::class,
            JiraApiService::class
        );
        $this->app->bind(
            JiraProjectServiceInterface::class,
            JiraApiService::class
        );
        $this->app->bind(
            JiraRegisterWebhookInterface::class,
            JiraApiService::class
        );
        $this->app->bind(
            AsanaWorkspaceServiceInterface::class,
            AsanaApiService::class
        );
        $this->app->bind(
            AsanaProjectServiceInterface::class,
            AsanaApiService::class
        );
        $this->app->bind(
            AsanaRegisterWebhookInterface::class,
            AsanaApiService::class
        );
        $this->app->bind(
            ThrottleServiceInterface::class,
            ThrottleService::class
        );
        $this->app->bind(
            UpdateOrCreateWebhookRepositoryInterface::class,
            UpdateOrCreateWebhookRepository::class
        );
        $this->app->bind(
            WebhookHandlerFactoryInterface::class,
            WebhookHandlerFactory::class
        );
        $this->app->bind(
            EloquentWebhookRepositoryInterface::class,
            EloquentWebhookRepository::class
        );
        $this->app->bind(
            FindIntegrationByClosureRepositoryInterface::class,
            FindIntegrationByClosureRepository::class
        );
        $this->app->bind(
            FindIntegrationInstanceByClosureRepositoryInterface::class,
            FindIntegrationInstanceByClosureRepository::class
        );
        $this->app->bind(
            DeveloperActivityUpdateRepositoryInterface::class,
            DeveloperActivityUpdateRepository::class
        );
        $this->app->bind(
            DeveloperActivityCreateRepositoryInterface::class,
            DeveloperActivityCreateRepository::class
        );
        $this->app->bind(
            DeveloperActivityDeleteRepositoryInterface::class,
            DeveloperActivityDeleteRepository::class
        );
        $this->app->bind(
            DeveloperActivityIndexRepositoryInterface::class,
            DeveloperActivityIndexRepository::class
        );
        $this->app->bind(
            DeveloperActivityFindRepositoryInterface::class,
            DeveloperActivityFindRepository::class
        );
        $this->app->bind(
            DeveloperActivityWithIntegrationDataRepositoryInterface::class,
            DeveloperActivityWithIntegrationDataRepository::class
        );
        $this->app->bind(
            DownloadReportStrategyInterface::class,
            DownloadReportStrategy::class
        );
        $this->app->bind(
            AchievementIndexRepositoryInterface::class,
            AchievementIndexRepository::class
        );
        $this->app->bind(
            DeveloperActivityIsApprovedUpdateRepositoryInterface::class,
            DeveloperActivityIsApprovedUpdateRepository::class
        );
        $this->app->bind(
            AchievementIsApprovedUpdateRepositoryInterface::class,
            AchievementIsApprovedUpdateRepository::class
        );
        $this->app->bind(
            CacheRepositoryInterface::class,
            CacheRepository::class
        );
        $this->app->bind(
            RecentActivityRepositoryInterface::class,
            RecentActivityRepository::class
        );
        $this->app->bind(
            ActivitiesStatRepositoryInterface::class,
            ActivitiesStatRepository::class
        );
        $this->app->bind(
            ActivitiesPendingApprovalInterface::class,
            ActivitiesPendingApprovalRepository::class
        );
        $this->app->bind(
            ActivitiesIndexRepositoryInterface::class,
            ActivitiesIndexRepository::class
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
