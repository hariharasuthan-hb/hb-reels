<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            
            // Subscription Management
            'view subscriptions',
            'create subscriptions',
            'edit subscriptions',
            'delete subscriptions',
            'view subscription plans',
            'create subscription plans',
            'edit subscription plans',
            'delete subscription plans',
            
            // Activity Tracking
            'view activities',
            'create activities',
            'edit activities',
            'delete activities',
            'check in members',
            'check out members',
            
            // Payments
            'view payments',
            'create payments',
            'edit payments',
            'process refunds',
            
            // Invoices
            'view invoices',
            'create invoices',
            'edit invoices',
            'delete invoices',

            // Expenses
            'view expenses',
            'create expenses',
            'edit expenses',
            'delete expenses',

            // Incomes
            'view incomes',
            'create incomes',
            'edit incomes',
            'delete incomes',

            // Finances
            'view finances',
            
            // Reports
            'view reports',
            'export reports',

            // Communications
            'view announcements',
            'create announcements',
            'edit announcements',
            'delete announcements',
            'view notifications',
            'create notifications',
            'edit notifications',
            'delete notifications',
            'mark notifications read',
            
            // CMS Management
            'view cms pages',
            'create cms pages',
            'edit cms pages',
            'delete cms pages',
            'view cms content',
            'create cms content',
            'edit cms content',
            'delete cms content',
            'view landing page',
            'edit landing page',
            
            // Site Settings
            'view site settings',
            'edit site settings',
            
            // Payment Settings
            'view payment settings',
            'edit payment settings',
            
            // Banners
            'view banners',
            'create banners',
            'edit banners',
            'delete banners',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all()); // Admin gets all permissions

        $memberRole = Role::firstOrCreate(['name' => 'member']);
        $memberRole->syncPermissions([
            'view subscriptions',
            'view activities',
            'create activities',
            'view announcements',
            'view notifications',
            'mark notifications read',
        ]);
    }
}
