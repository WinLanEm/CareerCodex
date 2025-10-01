<?php

namespace App\Contracts\Services\Report;

use App\Enums\ReportTypeEnum;
use Illuminate\Http\Response;

interface DownloadReportStrategyInterface
{
    public function downloadReport(ReportTypeEnum $type,int $userId,?string $startDate = null,?string $endDate = null):Response;
}
