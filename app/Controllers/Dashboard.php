<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Services\DashboardService;

class Dashboard extends BaseController
{
    public function index()
    {
        $service = new DashboardService();
        $data = $service->data();

        return view('dashboard/index', [
            'summary' => $data['summary'],
            'growth' => $data['growth'],
            'statusDistribution' => $data['statusDistribution'],
            'topCities' => $data['topCities'],
            'recentActivities' => $data['recentActivities'],
            'generatedAt' => $data['generated_at'],
            'fromCache' => $data['from_cache'],
        ]);
    }

    public function refresh()
    {
        (new DashboardService())->clearCache();

        return redirect()->to('/dashboard')->with('success', 'Dashboard data refreshed');
    }
}
