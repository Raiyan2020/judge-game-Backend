<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // The permission catalogue MUST exist: `GroupPermissionService` looks a
        // permission up by key and denies when the row is missing, so without
        // this every non-owner permission check fails and the permissions
        // screen has nothing to toggle. Idempotent (updateOrCreate by key).
        $this->call(PermissionSeeder::class);

        // The achievement ladder: RoleActionSeeder first (the countable events),
        // then RoleTitleSeeder (the rungs that reference them). Without these
        // `GET /groups/{group}/achievements` returns [] and the screen is blank.
        $this->call(RoleActionSeeder::class);
        $this->call(RoleTitleSeeder::class);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
