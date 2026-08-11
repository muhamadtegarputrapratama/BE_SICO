<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
 use ApiResponse;

    public function __construct(protected DashboardService $dashboardService)
    {}

    public function index(Request $request): JsonResponse
    {
        $data = $this->dashboardService->getDashboardData($request->user());

        return $this->success('Data dashboard berhasil diambil.', $data);
    }
}
