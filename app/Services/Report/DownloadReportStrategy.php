<?php

namespace App\Services\Report;

use App\Contracts\Services\Report\DownloadReportStrategyInterface;
use App\Enums\ReportTypeEnum;
use App\Services\Report\Strategies\DownloadAchievementsReportStrategy;
use App\Services\Report\Strategies\DownloadDeveloperActivitiesReportStrategy;
use Illuminate\Http\Response;

class DownloadReportStrategy implements DownloadReportStrategyInterface
{
    public function downloadReport(ReportTypeEnum $type,int $userId, ?string $startDate = null, ?string $endDate = null):Response
    {
        return match ($type->value) {
            ReportTypeEnum::ACHIEVEMENT->value => app(DownloadAchievementsReportStrategy::class)->download($userId,$startDate,$endDate),
            ReportTypeEnum::DEVELOPER_ACTIVITY->value => app(DownloadDeveloperActivitiesReportStrategy::class)->download($userId,$startDate,$endDate)
        };
    }
}
