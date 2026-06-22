<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\AccountType;
use App\Enums\EntityType;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        $entities = [
            ['DGD - الجمارك', 'المديرية العامة للجمارك', 'Direction Générale des Douanes', EntityType::CUSTOMS, 16, 'douanes@mazayada.dz'],
            ['DGDPE - أملاك الدولة', 'المديرية العامة لأملاك الدولة', 'Direction Générale du Domaine Public', EntityType::STATE_PROPERTIES, 16, 'domaines@mazayada.dz'],
            ['APC خنشلة', 'المجلس الشعبي البلدي لخنشلة', 'APC Khenchela', EntityType::MUNICIPALITY, 40, 'apc.khenchela@mazayada.dz'],
            ['Huissier - محضر', 'مكتب المحضر القضائي', 'Office du Huissier de Justice', EntityType::JUDICIAL, 16, 'huissier@mazayada.dz'],
            ['DGI - الضرائب', 'المديرية العامة للضرائب', 'Direction Générale des Impôts', EntityType::TAX, 16, 'impots@mazayada.dz'],
        ];

        foreach ($entities as [$name, $ar, $fr, $type, $wilaya, $email]) {
            $entity = Entity::create([
                'name' => $name,
                'name_ar' => $ar,
                'name_fr' => $fr,
                'type' => $type,
                'wilaya_id' => $wilaya,
                'email' => $email,
            ]);

            $this->provisionAccount($entity, $email);
        }
    }

    /**
     * Each entity gets one institutional, read-only login (UserRole::ENTITY_VIEWER)
     * that views its own auctions and appeals. Keyed by email so it is idempotent.
     */
    private function provisionAccount(Entity $entity, string $email): void
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'account_type' => AccountType::INSTITUTION,
                'role' => UserRole::ENTITY_VIEWER,
                'entity_id' => $entity->id,
                'password' => 'Entity@2026!',
                'first_name_ar' => $entity->name_ar,
                'last_name_ar' => '',
                'first_name_fr' => $entity->name_fr,
                'kyc_status' => KycStatus::COMPLETE,
                'kyc_completed_at' => now(),
                'account_status' => AccountStatus::ACTIVE,
                'email_verified' => true,
            ]
        );

        $user->syncRoles([UserRole::ENTITY_VIEWER->value]);
    }
}
