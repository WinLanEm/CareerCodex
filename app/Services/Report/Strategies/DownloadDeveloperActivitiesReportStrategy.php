<?php

namespace App\Services\Report\Strategies;

use App\Contracts\Repositories\DeveloperActivities\DeveloperActivityIndexRepositoryInterface;
use Illuminate\Http\Response;

class DownloadDeveloperActivitiesReportStrategy extends BaseDownloadReportStrategy
{
    public function __construct(
        readonly private DeveloperActivityIndexRepositoryInterface $repository,
    )
    {
    }

    public function download(int $userId,?string $startDate,?string $endDate):Response
    {
        $activities = $this->repository->index(1,50,$userId,null,true,$startDate,$endDate);
        return $this->generatePdfFromView(
            'reports.developer_activities',
            ['activities' => $activities->items()],
            'developer-activities-report.pdf'
        );
    }
}
