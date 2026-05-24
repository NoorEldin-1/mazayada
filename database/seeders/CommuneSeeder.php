<?php

namespace Database\Seeders;

use App\Models\Commune;
use Illuminate\Database\Seeder;

class CommuneSeeder extends Seeder
{
    public function run(): void
    {
        // Khenchela (40) — sample communes
        $communes = [
            [40, '4001', 'خنشلة', 'Khenchela', '40000'],
            [40, '4002', 'بابار', 'Babar', '40001'],
            [40, '4003', 'قايس', 'Kais', '40002'],
            [40, '4004', 'بوحمامة', 'Bouhmama', '40003'],
            [40, '4005', 'الحامة', 'El Hamma', '40004'],
            [40, '4006', 'عين الطويلة', 'Ain Touila', '40005'],
            [40, '4007', 'تاوزيانت', 'Taouzianat', '40006'],
            [40, '4008', 'بغاي', 'Baghai', '40007'],
            [40, '4009', 'أنسيغة', 'Ensigha', '40008'],
            [40, '4010', 'الولجة', 'El Oueldja', '40009'],
            [40, '4011', 'رمصة', 'Remila', '40010'],
            [40, '4012', 'شلية', 'Chelia', '40011'],
            [40, '4013', 'جلال', 'Djellal', '40012'],
            [40, '4014', 'متوسة', 'M\'Toussa', '40013'],
            [40, '4015', 'عين طويلة', 'Ain Touila', '40014'],
            [40, '4016', 'المحمل', 'El Mahmal', '40015'],
            [40, '4017', 'طامزة', 'Tamza', '40016'],
            [40, '4018', 'يابوس', 'Yabous', '40017'],
            [40, '4019', 'خيران', 'Khirane', '40018'],
            [40, '4020', 'أولاد رشاش', 'Ouled Rechache', '40019'],
            [40, '4021', 'شنشار', 'Chenchar', '40020'],
        ];

        // Algiers (16) — sample communes
        $algiers = [
            [16, '1601', 'الجزائر الوسطى', 'Alger Centre', '16000'],
            [16, '1602', 'سيدي امحمد', 'Sidi M\'Hamed', '16001'],
            [16, '1603', 'المدنية', 'El Madania', '16002'],
            [16, '1604', 'بلوزداد', 'Belouizdad', '16003'],
            [16, '1605', 'باب الوادي', 'Bab El Oued', '16004'],
            [16, '1606', 'بولوغين', 'Bologhine', '16005'],
            [16, '1607', 'القصبة', 'Casbah', '16006'],
            [16, '1608', 'حسين داي', 'Hussein Dey', '16007'],
            [16, '1609', 'القبة', 'Kouba', '16008'],
            [16, '1610', 'باب الزوار', 'Bab Ezzouar', '16009'],
        ];

        // Oran (31)
        $oran = [
            [31, '3101', 'وهران', 'Oran', '31000'],
            [31, '3102', 'السانية', 'Es Sénia', '31001'],
            [31, '3103', 'بئر الجير', 'Bir El Djir', '31002'],
            [31, '3104', 'المرسى الكبير', 'Mers El Kébir', '31003'],
        ];

        foreach (array_merge($communes, $algiers, $oran) as [$wilaya_id, $code, $ar, $fr, $postal]) {
            Commune::create([
                'wilaya_id' => $wilaya_id,
                'code' => $code,
                'name_ar' => $ar,
                'name_fr' => $fr,
                'postal_code' => $postal,
            ]);
        }
    }
}
