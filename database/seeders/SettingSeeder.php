<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['name' => 'dashboard_logo'],
            [
                'type' => 'file',
                'value' => [
                    'en' => '_dashboard/sidebar-logo.png',
                    'ar' => '_dashboard/sidebar-logo.png',
                ],
                'page' => 'Branding',
                'slug' => 'branding',
                'title' => 'Dashboard Logo',
            ],
        );

        Setting::updateOrCreate(
            ['name' => 'quick_services'],
            [
                'type' => 'text',
                'value' => [
                    'en' => '',
                    'ar' => '',
                ],
                'page' => 'الخدمات السريعة',
                'slug' => 'quick-services',
                'title' => 'الخدمات السريعة',
            ],
        );

        Setting::updateOrCreate(
            ['name' => 'offers'],
            [
                'type' => 'text',
                'value' => [
                    'en' => '',
                    'ar' => '',
                ],
                'page' => 'العروض',
                'slug' => 'offers',
                'title' => 'العروض',
            ],
        );
    }
}
