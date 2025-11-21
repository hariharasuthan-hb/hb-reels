<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

/**
 * Controller for viewing user activity summaries in the admin panel.
 * 
 * Handles displaying a summary of member activities including total check-ins,
 * today's check-ins, and last activity date. Shows all members. Accessible by
 * admin role with 'view activities' permission.
 */
class UserActivityController extends Controller
{
    /**
     * Display a listing of users (members) with their activity summary.
     * Shows all members for admins.
     */
    public function index(): View
    {
        // Admin sees all members
        $members = User::role('member')
            ->with(['roles'])
            ->get();
        
        // Add activity summary for each member
        $members = $members->map(function ($member) {
            $totalCheckIns = \App\Models\ActivityLog::where('user_id', $member->id)->count();
            $todayCheckIns = \App\Models\ActivityLog::where('user_id', $member->id)
                ->where('date', today())
                ->count();
            $lastActivity = \App\Models\ActivityLog::where('user_id', $member->id)
                ->latest('date')
                ->first();
            
            $member->total_check_ins = $totalCheckIns;
            $member->today_check_ins = $todayCheckIns;
            $member->last_activity = $lastActivity;
            
            return $member;
        });
        
        return view('admin.user-activity.index', compact('members'));
    }
}

