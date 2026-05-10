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
                    'ar' => 'دعوة أعضاء جدد',
                    'en' => 'Invite New Members',
                ],
                'key'   => 'invite_members',
            ],
            [
                'group' => 'invitation_settings',
                'name'  => [
                    'ar' => 'تعيين شخصية الأعضاء',
                    'en' => 'Set Member Persona',
                ],
                'key'   => 'set_persona',
            ],
            [
                'group' => 'invitation_settings',
                'name'  => [
                    'ar' => 'إلغاء دعوة الأعضاء',
                    'en' => 'Revoke Invitations',
                ],
                'key'   => 'remove_members',
            ],

            // =====================
            // التصويت
            // =====================
            [
                'group' => 'voting',
                'name'  => [
                    'ar' => 'تحديد حقوق التصويت',
                    'en' => 'Set Voting Rights',
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