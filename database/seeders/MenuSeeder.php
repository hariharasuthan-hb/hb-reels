<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            [
                'title' => 'About',
                'url' => '#about',
                'route' => null,
                'icon' => null,
                'order' => 1,
                'is_active' => true,
                'target' => '_self',
            ],
            [
                'title' => 'Services',
                'url' => '#services',
                'route' => null,
                'icon' => null,
                'order' => 2,
                'is_active' => true,
                'target' => '_self',
            ],
            [
                'title' => 'Pages',
                'url' => null,
                'route' => null, // Will be handled dynamically to show CMS pages
                'icon' => null,
                'order' => 3,
                'is_active' => true,
                'target' => '_self',
            ],
            [
                'title' => 'Contact',
                'url' => '#contact',
                'route' => null,
                'icon' => null,
                'order' => 4,
                'is_active' => true,
                'target' => '_self',
            ],
            [
                'title' => 'Dashboard',
                'url' => null,
                'route' => 'member.dashboard',
                'icon' => null,
                'order' => 5,
                'is_active' => true,
                'target' => '_self',
            ],
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(
                ['title' => $menu['title']],
                $menu
            );
        }
    }
}
