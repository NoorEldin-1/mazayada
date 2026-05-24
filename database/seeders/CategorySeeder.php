<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['مركبات', 'Véhicules', 'Vehicles', 'car'],
            ['عقارات', 'Immobilier', 'Real Estate', 'building'],
            ['معدات صناعية', 'Équipements industriels', 'Industrial Equipment', 'factory'],
            ['إلكترونيات', 'Électronique', 'Electronics', 'monitor'],
            ['خردة', 'Ferraille', 'Scrap', 'recycle'],
            ['منتجات زراعية', 'Produits agricoles', 'Agricultural Produce', 'wheat'],
            ['أثاث مكتبي', 'Mobilier de bureau', 'Office Furniture', 'armchair'],
            ['أخرى', 'Autres', 'Other', 'package'],
        ];

        foreach ($categories as [$ar, $fr, $en, $icon]) {
            Category::create(['name_ar' => $ar, 'name_fr' => $fr, 'name_en' => $en, 'icon' => $icon]);
        }
    }
}
