<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AutoCheckoutMemberJob;
use App\Models\ActivityLog;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * API Controller for member-related endpoints.
 * 
 * Reuses existing MemberController logic and repositories
 * to provide JSON API responses for member functionality.
 * 
 * All endpoints require authentication and member role.
 */
class MemberController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Get authenticated member's profile.
     * 
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = auth()->user()->load('roles');
        
        return $this->successResponse('Profile retrieved successfully', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'age' => $user->age,
            'gender' => $user->gender,
            'address' => $user->address,
            'status' => $user->status,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ], 'Profile retrieved successfully');
    }

    /**
     * Update member profile information.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $this->userRepository->updateWithRole($user, $validated);
        $user->refresh();

        return $this->successResponse('Profile updated successfully', [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
        ]);
    }

    /**
     * Update member password.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse('Password updated successfully');
    }

    /**
     * Get member dashboard data.
     * 
     * Reuses logic from FrontendMemberController::dashboard()
     * 
     * @return JsonResponse
     */
    public function dashboard(): JsonResponse
    {
        $user = auth()->user();
        
        // Get active subscription
        $activeSubscription = $user->subscriptions()
            ->with('subscriptionPlan')
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereNull('next_billing_at')
                      ->orWhere('next_billing_at', '>=', now());
            })
            ->first();
        
        // Get active subscription plans if no active subscription
        $subscriptionPlans = null;
        if (!$activeSubscription) {
            $subscriptionPlans = \App\Models\SubscriptionPlan::active()
                ->orderBy('price', 'asc')
                ->get()
                ->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'plan_name' => $plan->plan_name,
                        'description' => $plan->description,
                        'price' => $plan->price,
                        'duration' => $plan->duration,
                        'duration_type' => $plan->duration_type,
                    ];
                });
        }
        
        // Count totals
        $stats = [
            'total_activities' => \App\Models\ActivityLog::where('user_id', $user->id)->count(),
        ];
        
        // Today's activity
        $hasActiveSubscription = (bool) $activeSubscription;
        $canTrackAttendance = $hasActiveSubscription;
        
        $todayActivity = null;
        if ($canTrackAttendance) {
            $activity = \App\Models\ActivityLog::todayForUser($user->id);
            if ($activity) {
                $todayActivity = [
                    'checked_in' => (bool) $activity->check_in_time,
                    'checked_out' => (bool) $activity->check_out_time,
                    'check_in_time' => $activity->check_in_time?->format('H:i:s'),
                    'check_out_time' => $activity->check_out_time?->format('H:i:s'),
                ];
            }
        }
        
        return $this->successResponse('Dashboard data retrieved successfully', [
            'active_subscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'status' => $activeSubscription->status,
                'plan' => $activeSubscription->subscriptionPlan ? [
                    'id' => $activeSubscription->subscriptionPlan->id,
                    'plan_name' => $activeSubscription->subscriptionPlan->plan_name,
                    'price' => $activeSubscription->subscriptionPlan->price,
                ] : null,
                'next_billing_at' => $activeSubscription->next_billing_at,
            ] : null,
            'subscription_plans' => $subscriptionPlans,
            'stats' => $stats,
            'today_activity' => $todayActivity,
            'can_track_attendance' => $canTrackAttendance,
        ]);
    }

    /**
     * Get member subscriptions.
     * 
     * @return JsonResponse
     */
    public function subscriptions(): JsonResponse
    {
        $user = auth()->user();
        
        $subscriptions = $user->subscriptions()
            ->with('subscriptionPlan')
            ->latest()
            ->get()
            ->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'plan' => $subscription->subscriptionPlan ? [
                        'id' => $subscription->subscriptionPlan->id,
                        'plan_name' => $subscription->subscriptionPlan->plan_name,
                        'price' => $subscription->subscriptionPlan->price,
                        'duration' => $subscription->subscriptionPlan->duration,
                        'duration_type' => $subscription->subscriptionPlan->duration_type,
                    ] : null,
                    'started_at' => $subscription->started_at,
                    'next_billing_at' => $subscription->next_billing_at,
                    'created_at' => $subscription->created_at,
                ];
            });
        
        return $this->successResponse('Subscriptions retrieved successfully', $subscriptions);
    }

    /**
     * Get member activities with pagination.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function activities(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $perPage = $request->get('per_page', 15);
        
        $activities = \App\Models\ActivityLog::where('user_id', $user->id)
            ->latest('date')
            ->paginate($perPage);
        
        $activities->getCollection()->transform(function ($activity) {
            return [
                'id' => $activity->id,
                'date' => $activity->date,
                'check_in_time' => $activity->check_in_time?->format('H:i:s'),
                'check_out_time' => $activity->check_out_time?->format('H:i:s'),
                'duration_minutes' => $activity->duration_minutes,
                'workout_summary' => $activity->workout_summary,
                'exercises_done' => $activity->exercises_done,
            ];
        });
        
        return $this->paginatedResponse($activities, 'Activities retrieved successfully');
    }

    /**
     * Check in - Reuses logic from FrontendMemberController.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkIn(Request $request): JsonResponse
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
            return $this->unauthorizedResponse('You need an active subscription to check in.');
        }
        
        // Check if already checked in today
        $existingCheckIn = ActivityLog::todayForUser($user->id);
        if ($existingCheckIn && $existingCheckIn->check_in_time) {
            return $this->errorResponse('You have already checked in today.', 400, ['checked_in' => true]);
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

        return $this->successResponse('Check-in successful!', [
            'checked_in' => true,
        ]);
    }

    /**
     * Check out - Reuses logic from FrontendMemberController.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function checkOut(Request $request): JsonResponse
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
            return $this->unauthorizedResponse('You need an active subscription to check out.');
        }

        $todayActivity = ActivityLog::todayForUser($user->id);

        if (!$todayActivity || !$todayActivity->check_in_time) {
            return $this->errorResponse('No active check-in found for today.', 400);
        }

        if ($todayActivity->check_out_time) {
            return $this->errorResponse('You have already checked out today.', 400);
        }

        $checkoutTime = now();
        $todayActivity->check_out_time = $checkoutTime;
        $todayActivity->duration_minutes = $todayActivity->check_in_time
            ? $todayActivity->check_in_time->diffInMinutes($checkoutTime)
            : 0;
        $todayActivity->save();

        return $this->successResponse('Checkout successful. Enjoy your rest!');
    }
}

