<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesCurrentSalon;
use App\Http\Controllers\Controller;
use App\Modules\Operations\Queries\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use ResolvesCurrentSalon;

    public function index(Request $request, DashboardMetrics $metrics): View
    {
        $salon = $this->currentSalon($request);

        return view('admin.dashboard', [
            'salon' => $salon,
            'stats' => $metrics->counts($salon),
            'recentAppointments' => $metrics->recentAppointments($salon),
            'openHandovers' => $metrics->latestOpenHandovers($salon),
        ]);
    }
}
