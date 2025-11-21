<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Jobs\AutoCheckoutMemberJob;
use App\Models\ActivityLog;
use App\Models\SubscriptionPlan;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class MemberController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Show member registration form.
     */
    public function register(): View
    {
        return view('frontend.member.register');
    }

    /**
     * Store new member registration.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        // Get member role ID
        $memberRole = Role::where('name', 'member')->first();
        
        if (!$memberRole) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Member role not found. Please contact administrator.']);
        }

        // Add role ID to validated data
        $validated['role'] = $memberRole->id;

        // Create user with member role using repository
        $this->userRepository->createWithRole($validated);

        return redirect()->route('login')
            ->with('success', 'Registration successful! Please login.');
    }

    /**
     * Show member dashboard.
     */
    public function dashboard(): View
    {
        $user = auth()->user();
        
        // Check if user has an active subscription
        $activeSubscription = $user->subscriptions()
            ->with('subscriptionPlan')
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                      ->orWhere('next_billing_at', '>=', now());
            })
            ->first();
        
        // Get active subscription plans if user has no active subscription
        $subscriptionPlans = null;
        if (!$activeSubscription) {
            $subscriptionPlans = SubscriptionPlan::active()
                ->orderBy('price', 'asc')
                ->get();
        }
        
        $hasActiveSubscription = (bool) $activeSubscription;
        $canTrackAttendance = $hasActiveSubscription;
        
        // Count totals for stats
        $totalActivities = \App\Models\ActivityLog::where('user_id', $user->id)->count();

        // Today's activity log state
        $todayActivity = $canTrackAttendance ? ActivityLog::todayForUser($user->id) : null;
        $checkedInToday = $canTrackAttendance ? (bool) ($todayActivity?->check_in_time) : false;
        $checkedOutToday = $canTrackAttendance ? (bool) ($todayActivity?->check_out_time) : false;
        $todayCheckInTimeFormatted = $checkedInToday && $todayActivity?->check_in_time
            ? $todayActivity->check_in_time->timezone(config('app.timezone', 'UTC'))->format('h:i A')
            : null;
        $todayCheckOutTimeFormatted = $checkedOutToday && $todayActivity?->check_out_time
            ? $todayActivity->check_out_time->timezone(config('app.timezone', 'UTC'))->format('h:i A')
            : null;
        
        return view('frontend.member.dashboard', compact(
            'activeSubscription', 
            'subscriptionPlans',
            'totalActivities',
            'checkedInToday',
            'checkedOutToday',
            'todayActivity',
            'todayCheckInTimeFormatted',
            'todayCheckOutTimeFormatted',
            'hasActiveSubscription',
            'canTrackAttendance'
        ));
    }

    /**
     * Show member profile.
     */
    public function profile(): View
    {
        $user = auth()->user();
        return view('frontend.member.profile', compact('user'));
    }

    /**
     * Update member profile information.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update($validated);

        return redirect()->route('member.profile')
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * Update member password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('member.profile')
            ->with('success', 'Password updated successfully.');
    }

    /**
     * Show member subscriptions.
     */
    public function subscriptions(): View
    {
        return view('frontend.member.subscriptions');
    }

    /**
     * Show member activities.
     */
    public function activities(): View
    {
        return view('frontend.member.activities');
    }

    /**
     * Manual check-in for member.
     */
    public function checkIn(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $today = now()->toDateString();

        // Ensure user has active subscription
        $hasActiveSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                    ->orWhere('next_billing_at', '>=', now());
            })
            ->exists();

        if (!$hasActiveSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'You need an active subscription to check in.',
            ], 403);
        }
        
        // Check if already checked in today
        $existingCheckIn = ActivityLog::todayForUser($user->id);
        if ($existingCheckIn && $existingCheckIn->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in today.',
                'checked_in' => true,
            ], 400);
        }
        
        // Create check-in record
        $activityLog = ActivityLog::create([
            'user_id' => $user->id,
            'date' => $today,
            'check_in_time' => now(),
            'check_in_method' => 'manual',
            'workout_summary' => 'Manual check-in',
        ]);

        // Schedule automatic checkout at end of day
        $delayUntil = Carbon::parse($today)->endOfDay();
        AutoCheckoutMemberJob::dispatch($activityLog->id)->delay($delayUntil);

        return response()->json([
            'success' => true,
            'message' => 'Check-in successful!',
            'checked_in' => true,
        ]);
    }

    /**
     * Manual check-out for member.
     */
    public function checkOut(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $hasActiveSubscription = $user->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                    ->orWhere('next_billing_at', '>=', now());
            })
            ->exists();

        if (!$hasActiveSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'You need an active subscription to check out.',
            ], 403);
        }

        $todayActivity = ActivityLog::todayForUser($user->id);

        if (!$todayActivity || !$todayActivity->check_in_time) {
            return response()->json([
                'success' => false,
                'message' => 'No active check-in found for today.',
            ], 400);
        }

        if ($todayActivity->check_out_time) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked out today.',
            ], 400);
        }

        $checkoutTime = now();
        $todayActivity->check_out_time = $checkoutTime;
        $todayActivity->duration_minutes = $todayActivity->check_in_time
            ? $todayActivity->check_in_time->diffInMinutes($checkoutTime)
            : 0;
        $todayActivity->save();

        return response()->json([
            'success' => true,
            'message' => 'Checkout successful. Enjoy your rest!',
        ]);
    }
}
