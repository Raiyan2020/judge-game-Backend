<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            // =====================
            // الحصانة
            // =====================
            [
                'group' => 'immunity',
                'name'  => [
                    'ar' => 'حصانة ضد القضايا',
                    'en' => 'Lawsuit Immunity',
                ],
                'key'   => 'lawsuit_immunity',
            ],

            // =====================
            // المجموعة
            // =====================
            [
                'group' => 'group_settings',
                'name'  => [
                    'ar' => 'تغيير صورة المجموعة',
                    'en' => 'Change Group Image',
                ],
                'key'   => 'change_group_image',
            ],
            [
                'group' => 'group_settings',
                'name'  => [
                    'ar' => 'تغيير اسم المجموعة',
                    'en' => 'Change Group Name',
                ],
                'key'   => 'change_group_name',
            ],

            // =====================
            // القوانين
            // =====================
            [
                'group' => 'laws',
                'name'  => [
                    'ar' => 'إضافة قوانين',
                    'en' => 'Add Laws',
                ],
                'key'   => 'add_laws',
            ],
            [
                'group' => 'laws',
                'name'  => [
                    'ar' => 'تعديل قانون',
                    'en' => 'Edit Law',
                ],
                'key'   => 'edit_law',
            ],
            [
                'group' => 'laws',
                'name'  => [
                    'ar' => 'حذف قانون',
                    'en' => 'Delete Law',
                ],
                'key'   => 'delete_law',
            ],

            // =====================
            // إعدادات الشخصيات
            // =====================
            [
                'group' => 'person_settings',
                'name'  => [
                    'ar' => 'تعيين المستشارين',
                    'en' => 'Assign Consultants',
                ],
                'key'   => 'assign_consultants',
            ],
            [
                'group' => 'person_settings',
                'name'  => [
                    'ar' => 'تعيين المحامين',
                    'en' => 'Assign Lawyers',
                ],
                'key'   => 'assign_lawyers',
            ],
            [
                'group' => 'person_settings',
                'name'  => [
                    'ar' => 'تعيين المواطنين',
                    'en' => 'Assign Citizens',
                ],
                'key'   => 'assign_citizens',
            ],

            // =====================
            // إعدادات الدعوات
            // =====================
            [
                'group' => 'invitation_settings',
                'name'  => [
                    'ar' => 'دعوة أعضاء',
                    'en' => 'Invite Members',
                ],
                'key'   => 'invite_members',
            ],
            [
                'group' => 'invitation_settings',
                'name'  => [
                    'ar' => 'تحديد الشخصية',
                    'en' => 'Set Persona',
                ],
                'key'   => 'set_persona',
            ],
            [
                'group' => 'invitation_settings',
                'name'  => [
                    'ar' => 'طرد أعضاء',
                    'en' => 'Remove Members',
                ],
                'key'   => 'remove_members',
            ],

            // =====================
            // التصويت
            // =====================
            [
                'group' => 'voting',
                'name'  => [
                    'ar' => 'حق التصويت',
                    'en' => 'Voting Rights',
                ],
                'key'   => 'vote_rights',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['key' => $permission['key']],
                $permission
            );
        }
    }
}
