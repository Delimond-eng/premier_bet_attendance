<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('station_id')->constrained('regions')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->after('region_id')->constrained('cities')->nullOnDelete();
        });

        Schema::create('user_station', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('sites')->cascadeOnDelete();
            $table->primary(['user_id', 'station_id']);
        });

        DB::table('users')
            ->whereNotNull('station_id')
            ->orderBy('id')
            ->get(['id', 'station_id'])
            ->each(function ($user) {
                DB::table('user_station')->insertOrIgnore([
                    'user_id' => $user->id,
                    'station_id' => $user->station_id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_station');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
