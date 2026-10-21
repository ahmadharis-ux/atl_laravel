<?php

namespace App\Http\Controllers\Admin\Report;

use App\Http\Controllers\Controller;
use App\Support\Report\CustomerReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $report = new CustomerReport($request->only(['segment', 'q', 'page']));
        $data = $report->build();

        return view('admin.report.customers', [
            'report' => $data,
            'exportQuery' => array_filter($request->only(['segment', 'q'])),
            'activeSegment' => (string) $request->query('segment', ''),
        ]);
    }
}
