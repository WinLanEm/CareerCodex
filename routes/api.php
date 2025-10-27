<?php

use App\Http\Controllers\Achievement\AchievementIndexController;
use App\Http\Controllers\Achievement\AchievementCreateController;
use App\Http\Controllers\Achievement\AchievementDeleteController;
use App\Http\Controllers\Achievement\AchievementFindController;
use App\Http\Controllers\Achievement\AchievementIsApprovedUpdateController;
use App\Http\Controllers\Achievement\AchievementUpdateController;
use App\Http\Controllers\AllActivities\ActivitiesIndexController;
use App\Http\Controllers\AllActivities\ActivitiesPendingApprovalController;
use App\Http\Controllers\AllActivities\ActivitiesStatController;
use App\Http\Controllers\AllActivities\RecentActivityController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityCreateController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityDeleteController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityFindController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityIndexController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityIsApprovedUpdateController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityUpdateController;
use App\Http\Controllers\Report\DownloadReportController;
use App\Http\Controllers\Services\Auth\SocialAuthController;
use App\Http\Controllers\Services\Auth\SocialRedirectController;
use App\Http\Controllers\Services\Service\IntegrationCallbackController;
use App\Http\Controllers\Services\Service\IntegrationRedirectController;
use App\Http\Controllers\Services\Service\SyncIntegrationController;
use App\Http\Controllers\User\UserUpdateController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\LogoutController;
use App\Http\Controllers\User\MeController;
use App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\User\ResendVerifyEmailController;
use App\Http\Controllers\User\VerifyEmailController;
use App\Http\Controllers\Webhook\WebhookCallbackController;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;

Route::post('/register',RegisterController::class)->name('register');
Route::post('/login',LoginController::class)->name('login');
Route::post('/email/verify', VerifyEmailController::class)->name('verify');
Route::post('/email/verify/resend', ResendVerifyEmailController::class)->middleware(['throttle:1,2'])->name('resend');

Route::middleware([
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    EncryptCookies::class,
])->group(function () {
    Route::get('/auth/{provider}/redirect', SocialRedirectController::class)->name('auth.redirect');
    Route::get('/auth/{provider}/callback', SocialAuthController::class)->name('auth.callback');
    Route::get('/service/{service}/callback', IntegrationCallbackController::class)->name('service.callback');
});

Route::post('/webhook/{service}', WebhookCallbackController::class)->name('webhook');
//https://github.com/apps/ВАШЕ-ПРИЛОЖЕНИЕ/installations/new для гитхаба перед переходом и service/redirect нужно сначала чтобы пользователь скачал приложение к своему гитхаб и дал разрешения на получение уведов с конкретных репозиториев

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout',LogoutController::class)->name('logout');
    Route::get('/me',MeController::class)->name('me');
    Route::post('/user/update',UserUpdateController::class)->name('user.update');

    Route::patch('/achievements/approved',AchievementIsApprovedUpdateController::class)->name('achievements.approved');
    Route::get('/achievements',AchievementIndexController::class)->name('achievements.index');
    Route::get('/achievements/{id}',AchievementFindController::class)->name('achievement.find');
    Route::post('/achievements',AchievementCreateController::class)->name('achievement.create');
    Route::patch('/achievements/{id}',AchievementUpdateController::class)->name('achievement.update');
    Route::delete('/achievements/{id}',AchievementDeleteController::class)->name('achievement.delete');

    Route::get('/service/{service}/redirect', IntegrationRedirectController::class)->name('service.redirect');
    Route::get('/service/sync', SyncIntegrationController::class)->middleware(['throttle:1,5'])->name('service.sync');

    Route::patch('/developer-activities/approved',DeveloperActivityIsApprovedUpdateController::class)->name('developer.activity.is_approved.update');
    Route::get('/developer-activities',DeveloperActivityIndexController::class)->name('developer.activity.index');
    Route::get('/developer-activities/{id}',DeveloperActivityFindController::class)->name('developer.activity.find');
    Route::post('/developer-activities',DeveloperActivityCreateController::class)->name('developer.activity.create');
    Route::patch('/developer-activities/{id}',DeveloperActivityUpdateController::class)->name('developer.activity.update');
    Route::delete('/developer-activities/{id}',DeveloperActivityDeleteController::class)->name('developer.activity.delete');

    Route::get('/recent-activities',RecentActivityController::class)->name('recent.activities');
    Route::get('/activities/stats',ActivitiesStatController::class)->name('activities.stats');
    Route::get('/activities',ActivitiesIndexController::class)->name('activities.index');
    Route::get('/activities/pending-approvals',ActivitiesPendingApprovalController::class)->name('activities.pending_approvals');

    Route::get('/reports/download',DownloadReportController::class)->name('report.download');
});
