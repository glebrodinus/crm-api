<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Deal;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function kpi()
    {
        $userId = Auth::id();

        $accountsCount = Account::where('created_by_user_id', $userId)->count();

        $openTasksCount = Task::where('created_by_user_id', $userId)
            ->whereNull('completed_at')
            ->count();

        $openDealsCount = Deal::where('created_by_user_id', $userId)
            ->whereIn('status', ['pending', 'quoted', 'drafted'])
            ->count();

        $kpiData = [
            'active_accounts_count' => $accountsCount,
            'open_tasks_count' => $openTasksCount,
            'open_deals_count' => $openDealsCount,
        ];

        return $this->success('Dashboard KPI retrieved successfully', $kpiData);    
    }
}
