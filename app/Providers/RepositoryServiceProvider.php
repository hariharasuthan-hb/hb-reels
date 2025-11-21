<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\CmsContent;
use App\Models\CmsPage;
use App\Models\Expense;
use App\Models\InAppNotification;
use App\Models\Income;
use App\Models\Payment;
use App\Models\PaymentSetting;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\WorkoutVideo;
use App\Repositories\Eloquent\AnnouncementRepository;
use App\Repositories\Eloquent\CmsContentRepository;
use App\Repositories\Eloquent\CmsPageRepository;
use App\Repositories\Eloquent\ExpenseRepository;
use App\Repositories\Eloquent\InAppNotificationRepository;
use App\Repositories\Eloquent\IncomeRepository;
use App\Repositories\Eloquent\PaymentRepository;
use App\Repositories\Eloquent\PaymentSettingRepository;
use App\Repositories\Eloquent\SubscriptionPlanRepository;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\WorkoutVideoRepository;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use App\Repositories\Interfaces\CmsContentRepositoryInterface;
use App\Repositories\Interfaces\CmsPageRepositoryInterface;
use App\Repositories\Interfaces\ExpenseRepositoryInterface;
use App\Repositories\Interfaces\InAppNotificationRepositoryInterface;
use App\Repositories\Interfaces\IncomeRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use App\Repositories\Interfaces\PaymentSettingRepositoryInterface;
use App\Repositories\Interfaces\SubscriptionPlanRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\WorkoutVideoRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind CMS Page Repository
        $this->app->bind(
            CmsPageRepositoryInterface::class,
            function ($app) {
                return new CmsPageRepository(new CmsPage());
            }
        );

        // Bind Announcement Repository
        $this->app->bind(
            AnnouncementRepositoryInterface::class,
            function ($app) {
                return new AnnouncementRepository(new Announcement());
            }
        );

        // Bind CMS Content Repository
        $this->app->bind(
            CmsContentRepositoryInterface::class,
            function ($app) {
                return new CmsContentRepository(new CmsContent());
            }
        );

        // Bind User Repository
        $this->app->bind(
            UserRepositoryInterface::class,
            function ($app) {
                return new UserRepository(new User());
            }
        );

        // Bind Subscription Plan Repository
        $this->app->bind(
            SubscriptionPlanRepositoryInterface::class,
            function ($app) {
                return new SubscriptionPlanRepository(new SubscriptionPlan());
            }
        );

        // Bind Payment Setting Repository
        $this->app->bind(
            PaymentSettingRepositoryInterface::class,
            function ($app) {
                return new PaymentSettingRepository(new PaymentSetting());
            }
        );

        // Bind Payment Repository
        $this->app->bind(
            PaymentRepositoryInterface::class,
            function ($app) {
                return new PaymentRepository(new Payment());
            }
        );

        // Bind Expense Repository
        $this->app->bind(
            ExpenseRepositoryInterface::class,
            function ($app) {
                return new ExpenseRepository(new Expense());
            }
        );

        // Bind In-App Notification Repository
        $this->app->bind(
            InAppNotificationRepositoryInterface::class,
            function ($app) {
                return new InAppNotificationRepository(new InAppNotification());
            }
        );

        // Bind Income Repository
        $this->app->bind(
            IncomeRepositoryInterface::class,
            function ($app) {
                return new IncomeRepository(new Income());
            }
        );

        // Bind Workout Video Repository
        $this->app->bind(
            WorkoutVideoRepositoryInterface::class,
            function ($app) {
                return new WorkoutVideoRepository(new WorkoutVideo());
            }
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
