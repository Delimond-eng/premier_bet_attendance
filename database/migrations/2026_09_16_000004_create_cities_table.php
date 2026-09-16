<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->string('name');
            $table->string('timezone')->default('Africa/Kinshasa');
            $table->timestamps();
            $table->unique(['region_id', 'name']);
            $table->index('name');
        });

        $now = now();
        $regions = DB::table('regions')->get(['id', 'cities', 'timezone']);

        foreach ($regions as $region) {
            $cities = json_decode((string) $region->cities, true) ?: [];
            $cities = array_values(array_unique(array_filter(array_map('trim', $cities))));

            foreach ($cities as $name) {
                DB::table('cities')->insert([
                    'region_id' => $region->id,
                    'name' => $name,
                    'timezone' => $region->timezone ?: 'Africa/Kinshasa',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
