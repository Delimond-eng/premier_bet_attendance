<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code', 10)->unique();
            $table->string('capital');
            $table->json('cities')->nullable();
            $table->string('timezone')->default('Africa/Kinshasa');
            $table->timestamps();
        });

        $now = now();
        $regions = [
            ['name' => 'Kinshasa', 'code' => 'CD-KN', 'capital' => 'Kinshasa', 'cities' => ['Kinshasa'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Kongo-Central', 'code' => 'CD-BC', 'capital' => 'Matadi', 'cities' => ['Matadi', 'Boma', 'Mbanza-Ngungu'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Kwango', 'code' => 'CD-KG', 'capital' => 'Kenge', 'cities' => ['Kenge', 'Popokabaka'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Kwilu', 'code' => 'CD-KW', 'capital' => 'Bandundu', 'cities' => ['Bandundu', 'Kikwit', 'Idiofa'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Mai-Ndombe', 'code' => 'CD-MN', 'capital' => 'Inongo', 'cities' => ['Inongo', 'Kutu'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Équateur', 'code' => 'CD-EQ', 'capital' => 'Mbandaka', 'cities' => ['Mbandaka', 'Gemena', 'Bikoro'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Mongala', 'code' => 'CD-MO', 'capital' => 'Lisala', 'cities' => ['Lisala', 'Bumba'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Nord-Ubangi', 'code' => 'CD-NU', 'capital' => 'Gbadolite', 'cities' => ['Gbadolite', 'Bosobolo'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Sud-Ubangi', 'code' => 'CD-SU', 'capital' => 'Gemena', 'cities' => ['Gemena', 'Libenge', 'Zongo'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Tshuapa', 'code' => 'CD-TU', 'capital' => 'Boende', 'cities' => ['Boende', 'Befale'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Ituri', 'code' => 'CD-IT', 'capital' => 'Bunia', 'cities' => ['Bunia', 'Mahagi', 'Aru'], 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Nord-Kivu', 'code' => 'CD-NK', 'capital' => 'Goma', 'cities' => ['Goma', 'Beni', 'Butembo', 'Rutshuru'], 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Sud-Kivu', 'code' => 'CD-SK', 'capital' => 'Bukavu', 'cities' => ['Bukavu', 'Uvira', 'Baraka'], 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Maniema', 'code' => 'CD-MA', 'capital' => 'Kindu', 'cities' => ['Kindu', 'Kasongo'], 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Haut-Lomami', 'code' => 'CD-HL', 'capital' => 'Kamina', 'cities' => ['Kamina', 'Bukama'], 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Lualaba', 'code' => 'CD-LB', 'capital' => 'Kolwezi', 'cities' => ['Kolwezi', 'Dilolo'], 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Haut-Katanga', 'code' => 'CD-HK', 'capital' => 'Lubumbashi', 'cities' => ['Lubumbashi', 'Likasi', 'Kipushi'], 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Tanganyika', 'code' => 'CD-TA', 'capital' => 'Kalemie', 'cities' => ['Kalemie', 'Moba', 'Nyunzu'], 'timezone' => 'Africa/Lubumbashi'],
            ['name' => 'Haut-Uele', 'code' => 'CD-HU', 'capital' => 'Isiro', 'cities' => ['Isiro', 'Watsa', 'Dungu'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Bas-Uele', 'code' => 'CD-BU', 'capital' => 'Buta', 'cities' => ['Buta', 'Aketi'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Tshopo', 'code' => 'CD-TO', 'capital' => 'Kisangani', 'cities' => ['Kisangani', 'Isangi'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Lomami', 'code' => 'CD-LO', 'capital' => 'Kabinda', 'cities' => ['Kabinda', 'Lusambo', 'Mwene-Ditu'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Sankuru', 'code' => 'CD-SA', 'capital' => 'Lusambo', 'cities' => ['Lusambo', 'Tshumbe'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Kasaï', 'code' => 'CD-KS', 'capital' => 'Tshikapa', 'cities' => ['Tshikapa', 'Luebo'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Kasaï-Central', 'code' => 'CD-KC', 'capital' => 'Kananga', 'cities' => ['Kananga', 'Demba'], 'timezone' => 'Africa/Kinshasa'],
            ['name' => 'Kasaï-Oriental', 'code' => 'CD-KE', 'capital' => 'Mbuji-Mayi', 'cities' => ['Mbuji-Mayi', 'Mwene-Ditu', 'Kabinda'], 'timezone' => 'Africa/Kinshasa'],
        ];

        foreach ($regions as $region) {
            DB::table('regions')->insert(array_merge($region, [
                'cities' => json_encode($region['cities'], JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
