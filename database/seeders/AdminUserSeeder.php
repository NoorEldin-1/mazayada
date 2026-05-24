<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\EntityUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        $admin = User::create([
            'nin' => '109823041175663801',
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
        ]);

        // Entity Head for Khenchela APC
        $entityHead = User::create([
            'nin' => '109823041175663802',
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
        ]);

        // Link entity user
        $apc = Entity::where('name', 'LIKE', '%خنشلة%')->first();
        if ($apc) {
            EntityUser::create([
                'entity_id' => $apc->id,
                'user_id' => $entityHead->id,
                'username' => 'khenchela_admin',
                'password' => 'Khenchela@2026!',
                'full_name' => 'مسؤول خنشلة',
                'role' => 'ENTITY_HEAD',
            ]);
        }
    }
}
