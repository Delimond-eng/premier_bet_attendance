<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'station_id')) {
                $table->unsignedBigInteger('station_id')->nullable()->after('role');
                $table->index('station_id', 'users_station_id_idx');
                $table->foreign('station_id')->references('id')->on('sites')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'station_id')) {
                $table->dropForeign(['station_id']);
                $table->dropIndex('users_station_id_idx');
                $table->dropColumn('station_id');
            }
        });
    }
};
