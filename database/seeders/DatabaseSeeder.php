<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesPermissionsSeeder::class,
            SystemSettingsSeeder::class,
            WilayaSeeder::class,
            CommuneSeeder::class,
            CategorySeeder::class,
            EntitySeeder::class,
            AdminUserSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
