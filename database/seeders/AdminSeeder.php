<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Default dashboard admin. Idempotent via email key.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@judge.com'],
            [
                'name' => 'Admin',
                'phone' => '1000000000',
                'password' => '123456',
                'is_active' => true,
            ],
        );
    }
}
