<?php

namespace Database\Seeders;

use App\Enums\EntityType;
use App\Models\Entity;
use Illuminate\Database\Seeder;

class EntitySeeder extends Seeder
{
    public function run(): void
    {
        $entities = [
            ['DGD - الجمارك', 'المديرية العامة للجمارك', 'Direction Générale des Douanes', EntityType::CUSTOMS, 16],
            ['DGDPE - أملاك الدولة', 'المديرية العامة لأملاك الدولة', 'Direction Générale du Domaine Public', EntityType::STATE_PROPERTIES, 16],
            ['APC خنشلة', 'المجلس الشعبي البلدي لخنشلة', 'APC Khenchela', EntityType::MUNICIPALITY, 40],
            ['Huissier - محضر', 'مكتب المحضر القضائي', 'Office du Huissier de Justice', EntityType::JUDICIAL, 16],
            ['DGI - الضرائب', 'المديرية العامة للضرائب', 'Direction Générale des Impôts', EntityType::TAX, 16],
        ];

        foreach ($entities as [$name, $ar, $fr, $type, $wilaya]) {
            Entity::create([
                'name' => $name,
                'name_ar' => $ar,
                'name_fr' => $fr,
                'type' => $type,
                'wilaya_id' => $wilaya,
            ]);
        }
    }
}
