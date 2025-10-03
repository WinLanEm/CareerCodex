<?php

namespace App\Http\Controllers\Report;

use App\Contracts\Services\Report\DownloadReportStrategyInterface;
use App\Enums\ReportTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\DownloadReportRequest;

class DownloadReportController extends Controller
{
    public function __construct(
        readonly private DownloadReportStrategyInterface $downloadReportStrategy,
    )
    {
    }

    public function __invoke(DownloadReportRequest $request)
    {
        $type = ReportTypeEnum::tryFrom($request->get('type'));
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $userId = auth()->id();
        return $this->downloadReportStrategy->downloadReport($type,$userId,$startDate,$endDate);
    }
}
