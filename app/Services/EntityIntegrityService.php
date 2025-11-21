<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Expense;
use App\Models\Export;
use App\Models\Income;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;

/**
 * Central place to guard destructive actions (delete / inactivate)
 * across modules. Each method returns human friendly messages
 * describing why an entity cannot be modified.
 */
class EntityIntegrityService
{
    /**
     * Return every reason that prevents deleting a user record.
     *
     * @return array<int, string>
     */
    public function userDeletionBlockers(User $user): array
    {
        $messages = [];

        // 1. Active / pending subscriptions
        $subscriptionStatuses = [
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_TRIALING,
            Subscription::STATUS_PENDING,
            Subscription::STATUS_PAST_DUE,
        ];

        if ($user->subscriptions()->whereIn('status', $subscriptionStatuses)->exists()) {
            $messages[] = 'This user is still assigned to an active or pending subscription. Please cancel or reassign it before deleting the user.';
        }

        // 2. Financial history (payments / invoices)
        if (Payment::where('user_id', $user->id)->exists()) {
            $messages[] = 'This user has payment / invoice history. For auditing purposes the profile cannot be deleted.';
        }

        return $messages;
    }

    /**
     * Convenience helper: return only the first blocker message.
     */
    public function firstUserDeletionBlocker(User $user): ?string
    {
        $blockers = $this->userDeletionBlockers($user);

        return $blockers[0] ?? null;
    }

    /**
     * Reasons preventing deleting a subscription plan.
     *
     * @return array<int, string>
     */
    public function subscriptionPlanDeletionBlockers(SubscriptionPlan $plan): array
    {
        $messages = [];

        if (Subscription::where('subscription_plan_id', $plan->id)->exists()) {
            $messages[] = 'This subscription plan is assigned to one or more members. Move those members to a different plan before deleting.';
        }

        return $messages;
    }

    public function firstSubscriptionPlanDeletionBlocker(SubscriptionPlan $plan): ?string
    {
        $blockers = $this->subscriptionPlanDeletionBlockers($plan);

        return $blockers[0] ?? null;
    }

    /**
     * Reasons preventing deleting an announcement.
     */
    public function announcementDeletionBlockers(Announcement $announcement): array
    {
        $messages = [];

        $isPublished = $announcement->status === Announcement::STATUS_PUBLISHED;
        $stillVisible = !$announcement->expires_at || $announcement->expires_at->isFuture();

        if ($isPublished && $stillVisible) {
            $messages[] = 'This announcement is currently published. Archive it or wait until it expires before deleting.';
        }

        return $messages;
    }

    public function firstAnnouncementDeletionBlocker(Announcement $announcement): ?string
    {
        $blockers = $this->announcementDeletionBlockers($announcement);

        return $blockers[0] ?? null;
    }

    /**
     * Reasons preventing deleting an income record.
     */
    public function incomeDeletionBlockers(Income $income): array
    {
        $messages = [];

        // 1. Check if income is older than 30 days (audit trail)
        if ($income->received_at && $income->received_at->diffInDays(now()) > 30) {
            $messages[] = 'This income record is older than 30 days and cannot be deleted for audit purposes. Consider creating an adjustment entry instead.';
        }

        // 2. Check if income has been included in any completed exports
        $incomeDate = $income->received_at ? $income->received_at->format('Y-m-d') : null;
        $hasExport = false;
        
        if ($incomeDate) {
            $completedExports = Export::where('export_type', Export::TYPE_INCOMES)
                ->where('status', Export::STATUS_COMPLETED)
                ->whereNotNull('filters')
                ->get();
            
            foreach ($completedExports as $export) {
                $filters = $export->filters;
                if (is_array($filters) && isset($filters['date_from']) && isset($filters['date_to'])) {
                    $dateFrom = $filters['date_from'];
                    $dateTo = $filters['date_to'];
                    if ($incomeDate >= $dateFrom && $incomeDate <= $dateTo) {
                        $hasExport = true;
                        break;
                    }
                }
            }
        }

        if ($hasExport) {
            $messages[] = 'This income record has been included in financial exports and cannot be deleted. Create an adjustment entry to correct any errors.';
        }

        return $messages;
    }

    public function firstIncomeDeletionBlocker(Income $income): ?string
    {
        $blockers = $this->incomeDeletionBlockers($income);

        return $blockers[0] ?? null;
    }

    /**
     * Reasons preventing deleting an expense record.
     */
    public function expenseDeletionBlockers(Expense $expense): array
    {
        $messages = [];

        // 1. Check if expense is older than 30 days (audit trail)
        if ($expense->spent_at && $expense->spent_at->diffInDays(now()) > 30) {
            $messages[] = 'This expense record is older than 30 days and cannot be deleted for audit purposes. Consider creating an adjustment entry instead.';
        }

        // 2. Check if expense has been included in any completed exports
        $expenseDate = $expense->spent_at ? $expense->spent_at->format('Y-m-d') : null;
        $hasExport = false;
        
        if ($expenseDate) {
            $completedExports = Export::where('export_type', Export::TYPE_EXPENSES)
                ->where('status', Export::STATUS_COMPLETED)
                ->whereNotNull('filters')
                ->get();
            
            foreach ($completedExports as $export) {
                $filters = $export->filters;
                if (is_array($filters) && isset($filters['date_from']) && isset($filters['date_to'])) {
                    $dateFrom = $filters['date_from'];
                    $dateTo = $filters['date_to'];
                    if ($expenseDate >= $dateFrom && $expenseDate <= $dateTo) {
                        $hasExport = true;
                        break;
                    }
                }
            }
        }

        if ($hasExport) {
            $messages[] = 'This expense record has been included in financial exports and cannot be deleted. Create an adjustment entry to correct any errors.';
        }

        return $messages;
    }

    public function firstExpenseDeletionBlocker(Expense $expense): ?string
    {
        $blockers = $this->expenseDeletionBlockers($expense);

        return $blockers[0] ?? null;
    }
}


