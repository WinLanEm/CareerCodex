<?php

namespace App\Services\Report\Strategies;

use App\Contracts\Repositories\Achievement\AchievementIndexRepositoryInterface;
use Illuminate\Http\Response;

class DownloadAchievementsReportStrategy extends BaseDownloadReportStrategy
{
    public function __construct(
        readonly private AchievementIndexRepositoryInterface $repository,
    )
    {
    }

    public function download(int $userId,?string $startDate,?string $endDate):Response
    {
        $achievements = $this->repository->index(1,50,$userId,true,$startDate,$endDate);
        return $this->generatePdfFromView(
            'reports.achievements',
            ['achievements' => $achievements->items()],
            'achievements-report.pdf'
        );
    }
}
