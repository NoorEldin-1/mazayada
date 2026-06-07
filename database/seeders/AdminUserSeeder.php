<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\EntityUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $admin = User::updateOrCreate(
            ['nin' => '109823041175663801'],
            [
                'first_name_ar' => 'مشرف',
                'last_name_ar' => 'النظام',
                'first_name_fr' => 'Admin',
                'last_name_fr' => 'System',
                'birth_date' => '1990-01-01',
                'phone' => '0555000001',
                'email' => 'admin@mazayada.dz',
                'password' => 'Admin@2026!',
                'role' => UserRole::SUPER_ADMIN,
                'kyc_status' => KycStatus::COMPLETE,
                'kyc_completed_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
                'phone_verified' => true,
                'email_verified' => true,
            ]
        );
        $admin->syncRoles([UserRole::SUPER_ADMIN->value]);

        // Entity Head for Khenchela APC
        $entityHead = User::updateOrCreate(
            ['nin' => '109823041175663802'],
            [
                'first_name_ar' => 'مسؤول',
                'last_name_ar' => 'خنشلة',
                'first_name_fr' => 'Khenchela',
                'last_name_fr' => 'Admin',
                'birth_date' => '1985-06-15',
                'phone' => '0555000002',
                'email' => 'khenchela@mazayada.dz',
                'password' => 'Khenchela@2026!',
                'role' => UserRole::ENTITY_HEAD,
                'kyc_status' => KycStatus::COMPLETE,
                'kyc_completed_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
                'phone_verified' => true,
                'email_verified' => true,
            ]
        );
        $entityHead->syncRoles([UserRole::ENTITY_HEAD->value]);

        $apc = Entity::where('name', 'LIKE', '%خنشلة%')->first();
        if ($apc) {
            // Bind the staff User to its entity — this is what EntityScope reads
            // to isolate the entity head's view inside the admin dashboard.
            $entityHead->update(['entity_id' => $apc->id]);

            EntityUser::updateOrCreate(
                ['entity_id' => $apc->id, 'user_id' => $entityHead->id],
                [
                    'username' => 'khenchela_admin',
                    'password' => 'Khenchela@2026!',
                    'full_name' => 'مسؤول خنشلة',
                    'role' => 'ENTITY_HEAD',
                ]
            );
        }
    }
}
