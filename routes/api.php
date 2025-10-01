<?php

use App\Http\Controllers\Achievement\WorkspaceAchievementCreateController;
use App\Http\Controllers\Achievement\WorkspaceAchievementDeleteController;
use App\Http\Controllers\Achievement\WorkspaceAchievementFindController;
use App\Http\Controllers\Achievement\WorkspaceAchievementIndexController;
use App\Http\Controllers\Achievement\WorkspaceAchievementUpdateController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityDeleteController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityFindController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityIndexController;
use App\Http\Controllers\DeveloperActivity\DeveloperActivityUpdateController;
use App\Http\Controllers\Report\DownloadReportController;
use App\Http\Controllers\Services\Auth\SocialAuthController;
use App\Http\Controllers\Services\Auth\SocialRedirectController;
use App\Http\Controllers\Services\Service\IntegrationCallbackController;
use App\Http\Controllers\Services\Service\IntegrationRedirectController;
use App\Http\Controllers\Services\Service\SyncIntegrationController;
use App\Http\Controllers\User\AttachPasswordController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\LogoutController;
use App\Http\Controllers\User\MeController;
use App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\User\ResendVerifyEmailController;
use App\Http\Controllers\User\VerifyEmailController;
use App\Http\Controllers\Webhook\WebhookCallbackController;
use App\Http\Controllers\Workspace\WorkspaceCreateController;
use App\Http\Controllers\Workspace\WorkspaceDeleteController;
use App\Http\Controllers\Workspace\WorkspaceFindController;
use App\Http\Controllers\Workspace\WorkspaceIndexController;
use App\Http\Controllers\Workspace\WorkspaceUpdateController;
use Illuminate\Support\Facades\Route;

Route::post('/register',RegisterController::class)->name('register');
Route::post('/login',LoginController::class)->name('login');
Route::get('/auth/{provider}/redirect', SocialRedirectController::class)->name('auth.redirect');
Route::get('/auth/{provider}/callback', SocialAuthController::class)->name('auth.callback');
Route::get('/service/{service}/callback', IntegrationCallbackController::class)->name('service.callback');
Route::post('/webhook/{service}', WebhookCallbackController::class)->name('webhook');
//https://github.com/apps/ВАШЕ-ПРИЛОЖЕНИЕ/installations/new для гитхаба перед переходом и service/redirect нужно сначала чтобы пользователь скачал приложение к своему гитхаб и дал разрешения на получение уведов с конкретных репозиториев
Route::post('/email/verify', VerifyEmailController::class)->name('verify');
Route::post('/email/verify/resend', ResendVerifyEmailController::class)->middleware(['throttle:1,2'])->name('resend');


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout',LogoutController::class)->name('logout');
    Route::get('/me',MeController::class)->name('me');
    Route::post('/attach/password',AttachPasswordController::class)->name('attach.password');

    Route::get('/workspace',WorkspaceIndexController::class)->name('workspace.index');
    Route::get('/workspace/{id}',WorkspaceFindController::class)->name('workspace.find');
    Route::post('/workspace',WorkspaceCreateController::class)->name('workspace.create');
    Route::patch('/workspace/{id}',WorkspaceUpdateController::class)->name('workspace.update');
    Route::delete('/workspace/{id}',WorkspaceDeleteController::class)->name('workspace.delete');

    Route::get('/workspace/{id}/achievements',WorkspaceAchievementIndexController::class)->name('achievement.index');
    Route::get('/workspace/achievements/{id}',WorkspaceAchievementFindController::class)->name('achievement.find');
    Route::post('/workspace/{id}/achievements',WorkspaceAchievementCreateController::class)->name('achievement.create');
    Route::patch('/workspace/achievements/{id}',WorkspaceAchievementUpdateController::class)->name('achievement.update');
    Route::delete('/workspace/achievements/{id}',WorkspaceAchievementDeleteController::class)->name('achievement.delete');

    Route::get('/service/{service}/redirect', IntegrationRedirectController::class)->name('service.redirect');
    Route::get('/service/sync', SyncIntegrationController::class)->middleware(['throttle:1,5'])->name('service.sync');

    Route::get('/developer-activities',DeveloperActivityIndexController::class)->name('developer.activity.index');
    Route::get('/developer-activities/{id}',DeveloperActivityFindController::class)->name('developer.activity.find');
    Route::patch('/developer-activities/{id}',DeveloperActivityUpdateController::class)->name('developer.activity.update');
    Route::delete('/developer-activities/{id}',DeveloperActivityDeleteController::class)->name('developer.activity.delete');

    Route::get('/reports/download',DownloadReportController::class)->name('report.download');
});
