<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Services\AdminDashboardService;

class Home extends BaseController
{
    private AdminDashboardService $dashboardService;

    public function __construct()
    {
        $this->dashboardService = new AdminDashboardService();
    }

    public function index()
    {
        return view('admin/home', $this->layoutData() + [
            'title'      => 'Panel de Administración',
            'dashboard'  => $this->dashboardService->obtenerDashboard(),
        ]);
    }
}
