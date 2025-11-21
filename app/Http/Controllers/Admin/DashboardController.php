<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Controller for displaying the admin dashboard.
 * 
 * Handles the main dashboard view which displays overall system statistics.
 * Accessible by admin role.
 */
class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(): View
    {
        // Add admin dashboard statistics here
        $totalMembers = \App\Models\User::role('member')->count();
        $activeSubscriptions = \App\Models\Subscription::active()->count();
        $todayCheckIns = \App\Models\ActivityLog::where('date', today())->count();
        // Use final_amount if exists, otherwise use amount
        $dateColumn = \App\Models\Payment::getDateColumn();
        $query = \App\Models\Payment::whereMonth($dateColumn, now()->month)
            ->whereYear($dateColumn, now()->year);
        
        // Only filter by status if the column exists (accounting system may not have it)
        if (\App\Models\Payment::hasStatusColumn()) {
            $query->where('status', 'completed');
        }
        
        // Check if final_amount column exists
        $hasFinalAmount = \Illuminate\Support\Facades\Schema::hasColumn('payments', 'final_amount');
        
        if ($hasFinalAmount) {
            $monthlyRevenue = (float) $query->sum(DB::raw('COALESCE(final_amount, amount, 0)'));
        } else {
            $monthlyRevenue = (float) $query->sum('amount');
        }
        
        return view('admin.dashboard.index', compact(
            'totalMembers',
            'activeSubscriptions',
            'todayCheckIns',
            'monthlyRevenue'
        ));
    }
}
