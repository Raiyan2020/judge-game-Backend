<?php

namespace App\Console\Commands;

use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleActionSeeder;
use Database\Seeders\RoleTitleSeeder;
use Illuminate\Console\Command;

class SeedReferenceData extends Command
{
    protected $signature = 'app:seed-reference-data';

    protected $description = 'Seed the reference catalogues the app cannot work without (permissions, role actions, achievement ladder). Idempotent — safe to run on every deploy.';

    /**
     * The catalogues below are REFERENCE data, not sample data: the achievement
     * ladder, the per-role action list, and the permission catalogue are part of
     * the product, and features silently degrade without them (an unseeded
     * `role_titles` makes every achievements screen return an empty ladder; an
     * empty permission catalogue denies every non-owner check).
     *
     * `db:seed` cannot be used for this on a live server: `DatabaseSeeder` also
     * creates a `Test User` through a factory, so running it against production
     * would inject a fake account. This command runs ONLY the reference seeders.
     *
     * Every one of them is written with `updateOrCreate` against a unique key
     * (`role_actions.role+key`, `role_titles.role+tier`), so re-running changes
     * nothing that is already correct and repairs anything that drifted. That is
     * what makes it safe to wire into a deploy step and run on every release.
     */
    public function handle(): int
    {
        $seeders = [
            PermissionSeeder::class,
            RoleActionSeeder::class,
            RoleTitleSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $this->info('Seeding ' . class_basename($seeder) . ' ...');

            try {
                $this->callSilent('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
            } catch (\Throwable $e) {
                // Report and stop rather than leaving a half-seeded catalogue
                // looking like a success in the deploy log.
                $this->error(class_basename($seeder) . ' failed: ' . $e->getMessage());

                return self::FAILURE;
            }
        }

        $this->info('Reference data seeded.');

        return self::SUCCESS;
    }
}
