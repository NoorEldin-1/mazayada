<?php

namespace Database\Seeders;

use App\Models\Wilaya;
use Illuminate\Database\Seeder;

class WilayaSeeder extends Seeder
{
    public function run(): void
    {
        $wilayas = [
            [1,'01','أدرار','Adrar','Adrar'],[2,'02','الشلف','Chlef','Chlef'],[3,'03','الأغواط','Laghouat','Laghouat'],
            [4,'04','أم البواقي','Oum El Bouaghi','Oum El Bouaghi'],[5,'05','باتنة','Batna','Batna'],
            [6,'06','بجاية','Béjaïa','Bejaia'],[7,'07','بسكرة','Biskra','Biskra'],[8,'08','بشار','Béchar','Bechar'],
            [9,'09','البليدة','Blida','Blida'],[10,'10','البويرة','Bouïra','Bouira'],
            [11,'11','تمنراست','Tamanrasset','Tamanrasset'],[12,'12','تبسة','Tébessa','Tebessa'],
            [13,'13','تلمسان','Tlemcen','Tlemcen'],[14,'14','تيارت','Tiaret','Tiaret'],
            [15,'15','تيزي وزو','Tizi Ouzou','Tizi Ouzou'],[16,'16','الجزائر','Alger','Algiers'],
            [17,'17','الجلفة','Djelfa','Djelfa'],[18,'18','جيجل','Jijel','Jijel'],
            [19,'19','سطيف','Sétif','Setif'],[20,'20','سعيدة','Saïda','Saida'],
            [21,'21','سكيكدة','Skikda','Skikda'],[22,'22','سيدي بلعباس','Sidi Bel Abbès','Sidi Bel Abbes'],
            [23,'23','عنابة','Annaba','Annaba'],[24,'24','قالمة','Guelma','Guelma'],
            [25,'25','قسنطينة','Constantine','Constantine'],[26,'26','المدية','Médéa','Medea'],
            [27,'27','مستغانم','Mostaganem','Mostaganem'],[28,'28','المسيلة','M\'Sila','M\'Sila'],
            [29,'29','معسكر','Mascara','Mascara'],[30,'30','ورقلة','Ouargla','Ouargla'],
            [31,'31','وهران','Oran','Oran'],[32,'32','البيض','El Bayadh','El Bayadh'],
            [33,'33','إليزي','Illizi','Illizi'],[34,'34','برج بوعريريج','Bordj Bou Arréridj','Bordj Bou Arreridj'],
            [35,'35','بومرداس','Boumerdès','Boumerdes'],[36,'36','الطارف','El Tarf','El Tarf'],
            [37,'37','تندوف','Tindouf','Tindouf'],[38,'38','تيسمسيلت','Tissemsilt','Tissemsilt'],
            [39,'39','الوادي','El Oued','El Oued'],[40,'40','خنشلة','Khenchela','Khenchela'],
            [41,'41','سوق أهراس','Souk Ahras','Souk Ahras'],[42,'42','تيبازة','Tipaza','Tipaza'],
            [43,'43','ميلة','Mila','Mila'],[44,'44','عين الدفلى','Aïn Defla','Ain Defla'],
            [45,'45','النعامة','Naâma','Naama'],[46,'46','عين تموشنت','Aïn Témouchent','Ain Temouchent'],
            [47,'47','غرداية','Ghardaïa','Ghardaia'],[48,'48','غليزان','Relizane','Relizane'],
            [49,'49','تيميمون','Timimoun','Timimoun'],[50,'50','برج باجي مختار','Bordj Badji Mokhtar','Bordj Badji Mokhtar'],
            [51,'51','أولاد جلال','Ouled Djellal','Ouled Djellal'],[52,'52','بني عباس','Béni Abbès','Beni Abbes'],
            [53,'53','عين صالح','In Salah','In Salah'],[54,'54','عين قزام','In Guezzam','In Guezzam'],
            [55,'55','توقرت','Touggourt','Touggourt'],[56,'56','جانت','Djanet','Djanet'],
            [57,'57','المغير','El M\'Ghair','El M\'Ghair'],[58,'58','المنيعة','El Meniaa','El Meniaa'],
        ];

        foreach ($wilayas as [$id, $code, $ar, $fr, $en]) {
            Wilaya::create(compact('id', 'code') + ['name_ar' => $ar, 'name_fr' => $fr, 'name_en' => $en]);
        }
    }
}
