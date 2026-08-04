<?php

namespace Database\Seeders;

use App\Enums\CaseRole;
use App\Models\RoleAction;
use Illuminate\Database\Seeder;

class RoleActionSeeder extends Seeder
{
    public function run(): void
    {
        $actions =
            [
                [
                    'role' => CaseRole::JUDGE->value,
                    'key' => 'acquit_case',
                    'title' => [
                        'ar' => 'إذا حكم القاضي بالبراءة',
                        'en' => 'If the judge issues an acquittal',
                    ],

                ],
                [
                    'role' => CaseRole::JUDGE->value,
                    'key' => 'first_instance_judgment_case',
                    'title' => [
                        'ar' => 'إذا أصدر القاضي حكمًا في قضية ابتدائية',
                        'en' => 'If the judge issues a judgment in a first-instance case',
                    ],

                ],
                [
                    'role' => CaseRole::JUDGE->value,
                    'key' => 'appeal_judgment_case',
                    'title' => [
                        'ar' => 'إذا أصدر القاضي حكمًا في قضية استئناف',
                        'en' => 'If the judge issues a judgment in an appeal case',
                    ],

                ],
                [
                    'role' => CaseRole::JUDGE->value,
                    'key' => 'close_case_without_judgment',
                    'title' => [
                        'ar' => 'إذا أُغلقت القضية دون أي حكم',
                        'en' => 'If the case is closed without any judgment',
                    ],

                ],

                // [
                //     'role' => CaseRole::CONSULTANT->value,
                //     'key' => 'participate_case',
                //     'title' => [
                //         'ar' => 'إذا شارك المستشار في أي قضية',
                //         'en' => 'If the advisor participates in any case',
                //     ],

                // ],
                // [
                //     'role' => CaseRole::CONSULTANT->value,
                //     'key' => 'consulted_by_judge',
                //     'title' => [
                //         'ar' => 'إذا استشار القاضي المستشار',
                //         'en' => 'If the judge consults the advisor',
                //     ],

                // ],
                [
                    'role' => CaseRole::CONSULTANT->value,
                    'key' => 'issue_judgment_instead_of_judge_case',
                    'title' => [
                        'ar' => 'إذا أصدر المستشار حكمًا بدلًا من القاضي',
                        'en' => 'If the advisor issues a judgment instead of the judge',
                    ],
                    'points' => 3,
                ],
                [
                    'role' => CaseRole::CONSULTANT->value,
                    'key' => 'closed_without_judgment_case',
                    'title' => [
                        'ar' => 'إذا أُغلقت القضية دون أي حكم',
                        'en' => 'If the case is closed without any judgment',
                    ],

                ],

                [
                    'role' => CaseRole::PLAINTIFF_LAWYER->value,
                    'key' => 'closed_without_judgment_case',
                    'title' => [
                        'ar' => 'إذا أُغلقت القضية دون أي حكم',
                        'en' => 'If the case is closed without any judgment',
                    ],

                ],
                [
                    'role' => CaseRole::PLAINTIFF_LAWYER->value,
                    'key' => 'win_first_instance_case',
                    'title' => [
                        'ar' => 'إذا ربح المحامي دعوى الحكم الابتدائي',
                        'en' => 'If the lawyer wins the first instance case',
                    ],

                ],
                [
                    'role' => CaseRole::PLAINTIFF_LAWYER->value,
                    'key' => 'win_appeal_case',
                    'title' => [
                        'ar' => 'إذا ربح المحامي الاستئناف',
                        'en' => 'If the lawyer wins the appeal case',
                    ],

                ],
                [
                    'role' => CaseRole::PLAINTIFF_LAWYER->value,
                    'key' => 'case_closed_with_acquittal',
                    'title' => [
                        'ar' => 'إذا أغلقت القضية بحكم براءة',
                        'en' => 'If the case is closed with an acquittal judgment',
                    ],

                ],
                [
                    'role' => 'defendant_lawyer',
                    'key' => 'closed_without_judgment_case',
                    'title' => [
                        'ar' => 'إذا أُغلقت القضية دون صدور حكم',
                        'en' => 'If the case is closed without issuing a judgment',
                    ],
                ],
                [
                    'role' => 'defendant_lawyer',
                    'key' => 'win_first_instance_case',
                    'title' => [
                        'ar' => 'إذا ربح المحامي دعوى الحكم الابتدائي',
                        'en' => 'If the lawyer wins the first instance case',
                    ],

                ],
                [
                    'role' => 'defendant_lawyer',
                    'key' => 'win_appeal_case',
                    'title' => [
                        'ar' => 'إذا ربح المحامي الاستئناف',
                        'en' => 'If the lawyer wins the appeal case',
                    ],

                ],
                [
                    'role' => 'defendant_lawyer',
                    'key' => 'win_acquittal_case',
                    'title' => [
                        'ar' => 'إذا ربح المحامي البراءة',
                        'en' => 'If the lawyer wins the acquittal case',
                    ],

                ],

                // [
                //     'role' => CaseRole::WITNESS->value,
                //     'key' => 'provide_truthful_testimony',
                //     'title' => [
                //         'ar' => 'إذا شارك أي شخص في القضية كشاهد وكانت شهادته صحيحة',
                //         'en' => 'If a person participates in the case as a witness and their testimony is truthful',
                //     ],

                // ],
                // [
                //     'role' => CaseRole::WITNESS->value,
                //     'key' => 'provide_false_testimony',
                //     'title' => [
                //         'ar' => 'إذا شارك أي شخص في القضية كشاهد وكانت شهادته شهادة زور وثبت ذلك للقاضي',
                //         'en' => 'If a person participates in the case as a witness and provides false testimony proven by the judge',
                //     ],

                // ],

                [
                    'role' => 'citizen',
                    'key' => 'win_first_instance_case',
                    'title' => [
                        'ar' => 'إذا رفع مواطن دعوى وربح حكمًا ابتدائيًا',
                        'en' => 'If a citizen files a lawsuit and wins a first-instance judgment',
                    ],

                ],

                [
                    'role' => 'citizen',
                    'key' => 'win_appeal_case',
                    'title' => [
                        'ar' => 'إذا رفع مواطن دعوى وربح الاستئناف',
                        'en' => 'If a citizen files a lawsuit and wins the appeal',
                    ],

                ],

                [
                    'role' => 'citizen',
                    'key' => 'win_acquittal_case',
                    'title' => [
                        'ar' => 'إذا رفع مواطن دعوى وربح البراءة',
                        'en' => 'If a citizen files a lawsuit and wins an acquittal',
                    ],

                ],

                [
                    'role' => 'citizen',
                    'key' => 'week_without_lawsuit_against',
                    'title' => [
                        'ar' => 'إذا مرّ أسبوع على مواطن دون أن يرفع أحد دعوى قضائية ضده',
                        'en' => 'If a week passes without anyone filing a lawsuit against a citizen',
                    ],

                ],

                [
                    'role' => 'citizen',
                    'key' => 'judgment_executed_against',
                    'title' => [
                        'ar' => 'عند تنفيذ الحكم عليه',
                        'en' => 'When a judgment is executed against the citizen',
                    ],

                ],

                [
                    'role' => 'citizen',
                    'key' => 'file_lawsuit',
                    'title' => [
                        'ar' => 'عند رفع قضية',
                        'en' => 'When filing a lawsuit',
                    ],

                ],

                [
                    'role' => 'citizen',
                    'key' => 'lawsuit_filed_against',
                    'title' => [
                        'ar' => 'عند رفع قضية عليه',
                        'en' => 'When a lawsuit is filed against the citizen',
                    ],

                ],

                [
                    'role' => 'all',
                    'key' => 'propose_law_approved',
                    'title' => [
                        'ar' => 'إذا اقترح أي شخص قانونًا وتمّ إقراره',
                        'en' => 'If a person proposes a law and it is approved',
                    ],

                ],

                [
                    'role' => 'all',
                    'key' => 'participate_in_voting',
                    'title' => [
                        'ar' => 'إذا شارك أي شخص في عملية تصويت',
                        'en' => 'If a person participates in a voting process',
                    ],

                ],

                [
                    'role' => 'all',
                    'key' => 'amend_law_approved',
                    'title' => [
                        'ar' => 'إذا عدّل أي شخص قانونًا وتمّ إقراره',
                        'en' => 'If a person amends a law and the amendment is approved',
                    ],

                ],

                [
                    'role' => 'all',
                    'key' => 'weekly_participation_reward',
                    'title' => [
                        'ar' => 'تحصل جميع الشخصيات على نقطة واحدة في بداية الاسبوع',
                        'en' => 'All characters receive one point at the beginning of each week',
                    ],

                ],

            ];
        // Idempotent: keyed on (role, key) so re-running doesn't duplicate rows.
        foreach ($actions as $action) {
            RoleAction::updateOrCreate(
                ['role' => $action['role'], 'key' => $action['key']],
                $action
            );
        }
    }
}
