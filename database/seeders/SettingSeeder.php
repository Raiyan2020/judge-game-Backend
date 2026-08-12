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
    }
}
