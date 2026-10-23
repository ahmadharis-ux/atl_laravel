<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Support\Report\ProviderReport;
use App\Support\Report\ReportPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $period = ReportPeriod::fromRequest($request);
        $report = (new ProviderReport($period))->build();

        return view('admin.report.providers', [
            'period' => $period,
            'report' => $report,
            'exportQuery' => $period->toQuery(),
        ]);
    }
}
