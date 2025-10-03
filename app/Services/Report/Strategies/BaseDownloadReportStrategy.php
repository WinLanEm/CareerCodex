<?php

namespace App\Services\Report\Strategies;


use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

abstract class BaseDownloadReportStrategy
{
    abstract public function download(int $userId,?string $startDate,?string $endDate):Response;

    protected function generatePdfFromView(string $viewName, array $data, string $filename):Response
    {
        $pdf = Pdf::loadView($viewName, $data);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }
}
