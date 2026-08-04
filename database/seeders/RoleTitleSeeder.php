<?php

namespace Database\Seeders;

use App\Enums\CaseRole;
use App\Enums\GroupRole;
use App\Models\RoleAction;
use App\Models\RoleTitle;
use Illuminate\Database\Seeder;

/**
 * Seeds the per-role achievement ladder (`role_titles` + `role_title_requirements`)
 * that backs `GET /groups/{group}/achievements`. Definitions are ported from the
 * app's former mock ladder (the business spec) — now server-authoritative and
 * populated with LIVE counts. Only rungs whose actions actually have a working
 * counter are seeded (voting / weekly / law-proposal actions have no counter, so
 * they are deliberately omitted rather than shown as permanently 0).
 *
 * Idempotent: each rung is keyed on (role, tier); its requirements are resynced.
 * Depends on RoleActionSeeder having run (requirements reference role_actions).
 */
class RoleTitleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->ladders() as $role => $rungs) {
            foreach ($rungs as $rung) {
                $title = RoleTitle::updateOrCreate(
                    ['role' => $role, 'tier' => $rung['tier']],
                    [
                        'reward_points' => $rung['reward_points'],
                        'title' => $rung['title'],
                    ]
                );

                // Resync this rung's requirements to exactly the definition.
                $title->requirements()->delete();
                foreach ($rung['requirements'] as [$key, $count]) {
                    $action = $this->findAction($role, $key);
                    if (! $action) {
                        continue;
                    }
                    $title->requirements()->create([
                        'role_action_id' => $action->id,
                        'required_count' => $count,
                    ]);
                }
            }
        }
    }

    /**
     * Finds the seeded RoleAction for [key] within the CASE roles a group [role]
     * maps to (lawyer → plaintiff_lawyer/defendant_lawyer). Any match works —
     * rows sharing a key share the counter.
     */
    private function findAction(string $role, string $key): ?RoleAction
    {
        $roles = match ($role) {
            GroupRole::LAWYER->value => [
                CaseRole::PLAINTIFF_LAWYER->value,
                CaseRole::DEFENDANT_LAWYER->value,
                'all',
            ],
            default => [$role, 'all'],
        };

        return RoleAction::where('key', $key)->whereIn('role', $roles)->first();
    }

    private function ladders(): array
    {
        return [
            GroupRole::JUDGE->value => [
                [
                    'tier' => 1,
                    'reward_points' => 50,
                    'title' => ['ar' => 'قاضٍ مبتدئ', 'en' => 'Junior Judge'],
                    'requirements' => [['first_instance_judgment_case', 1]],
                ],
                [
                    'tier' => 2,
                    'reward_points' => 150,
                    'title' => ['ar' => 'قاضٍ نشِط', 'en' => 'Active Judge'],
                    'requirements' => [['first_instance_judgment_case', 10]],
                ],
                [
                    'tier' => 3,
                    'reward_points' => 250,
                    'title' => ['ar' => 'قاضٍ حازم', 'en' => 'Firm Judge'],
                    'requirements' => [
                        ['first_instance_judgment_case', 25],
                        ['appeal_judgment_case', 25],
                        ['acquit_case', 1],
                    ],
                ],
                [
                    'tier' => 4,
                    'reward_points' => 350,
                    'title' => ['ar' => 'قاضي القضاة', 'en' => 'Chief Justice'],
                    'requirements' => [
                        ['first_instance_judgment_case', 50],
                        ['appeal_judgment_case', 50],
                        ['acquit_case', 5],
                    ],
                ],
            ],

            GroupRole::LAWYER->value => [
                [
                    'tier' => 1,
                    'reward_points' => 50,
                    'title' => ['ar' => 'محامٍ مبتدئ', 'en' => 'Junior Lawyer'],
                    'requirements' => [['win_first_instance_case', 1]],
                ],
                [
                    'tier' => 2,
                    'reward_points' => 150,
                    'title' => ['ar' => 'محامٍ بارع', 'en' => 'Skilled Lawyer'],
                    'requirements' => [['win_first_instance_case', 10]],
                ],
                [
                    'tier' => 3,
                    'reward_points' => 300,
                    'title' => ['ar' => 'محامٍ مخضرم', 'en' => 'Veteran Lawyer'],
                    'requirements' => [
                        ['win_first_instance_case', 25],
                        ['win_appeal_case', 5],
                    ],
                ],
                [
                    'tier' => 4,
                    'reward_points' => 500,
                    'title' => ['ar' => 'محامي النخبة', 'en' => 'Elite Lawyer'],
                    'requirements' => [
                        ['win_first_instance_case', 50],
                        ['win_appeal_case', 10],
                        ['win_acquittal_case', 3],
                    ],
                ],
            ],

            GroupRole::CONSULTANT->value => [
                [
                    'tier' => 1,
                    'reward_points' => 50,
                    'title' => ['ar' => 'مستشار مبتدئ', 'en' => 'Junior Consultant'],
                    'requirements' => [['issue_judgment_instead_of_judge_case', 1]],
                ],
                [
                    'tier' => 2,
                    'reward_points' => 150,
                    'title' => ['ar' => 'مستشار موثوق', 'en' => 'Trusted Consultant'],
                    'requirements' => [['issue_judgment_instead_of_judge_case', 10]],
                ],
                [
                    'tier' => 3,
                    'reward_points' => 300,
                    'title' => ['ar' => 'كبير المستشارين', 'en' => 'Chief Consultant'],
                    'requirements' => [['issue_judgment_instead_of_judge_case', 25]],
                ],
            ],

            GroupRole::CITIZEN->value => [
                [
                    'tier' => 1,
                    'reward_points' => 50,
                    'title' => ['ar' => 'مواطن مبتدئ', 'en' => 'Junior Citizen'],
                    'requirements' => [['file_lawsuit', 1]],
                ],
                [
                    'tier' => 2,
                    'reward_points' => 150,
                    'title' => ['ar' => 'مواطن مبادر', 'en' => 'Active Citizen'],
                    'requirements' => [['file_lawsuit', 10]],
                ],
                [
                    'tier' => 3,
                    'reward_points' => 300,
                    'title' => ['ar' => 'مواطن مخضرم', 'en' => 'Veteran Citizen'],
                    'requirements' => [
                        ['file_lawsuit', 25],
                        ['win_first_instance_case', 5],
                    ],
                ],
                [
                    'tier' => 4,
                    'reward_points' => 500,
                    'title' => ['ar' => 'مواطن أسطوري', 'en' => 'Legendary Citizen'],
                    'requirements' => [
                        ['file_lawsuit', 50],
                        ['win_first_instance_case', 10],
                        ['win_appeal_case', 3],
                    ],
                ],
            ],
        ];
    }
}
