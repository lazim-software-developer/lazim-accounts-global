<?php

namespace App\Http\Controllers\Api\Common;

use App\Core\Services\ReportService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }


    public function getPagedInvoiceRecords(Request $request)
    {
        return $this->reportService->getPagedInvoiceRecords($request);
    }

    public function getPagedAgingRecords(Request $request)
    {
        return $this->reportService->getPagedAgingRecords($request);
    }

    public function getPagedReceiptsRecords(Request $request)
    {
        return $this->reportService->getPagedReceiptsRecords($request);
    }

    public function getPagedBillsRecords(Request $request)
    {
        return $this->reportService->getPagedBillsRecords($request);
    }
}
