<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Support\Report\OverviewReport;
use App\Support\Report\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = ReportPeriod::fromRequest($request);
        $report = (new OverviewReport($period))->build();

        return view('admin.report.overview', [
            'period' => $period,
            'report' => $report,
            'exportQuery' => $period->toQuery(),
        ]);
    }
}
