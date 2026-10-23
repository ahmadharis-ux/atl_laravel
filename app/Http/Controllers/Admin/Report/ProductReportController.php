<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Support\Report\ProductReport;
use App\Support\Report\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = ReportPeriod::fromRequest($request);
        $report = (new ProductReport($period))->build();

        return view('admin.report.products', [
            'period' => $period,
            'report' => $report,
            'exportQuery' => $period->toQuery(),
        ]);
    }
}
