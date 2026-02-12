<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presence_agents', function (Blueprint $table) {
            if (Schema::hasColumn('presence_agents', 'station_check_in_id')) {
                $table->foreign('station_check_in_id')->references('id')->on('sites')->nullOnDelete();
            }

            if (Schema::hasColumn('presence_agents', 'station_check_out_id')) {
                $table->foreign('station_check_out_id')->references('id')->on('sites')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('presence_agents', function (Blueprint $table) {
            $table->dropForeign(['station_check_in_id']);
            $table->dropForeign(['station_check_out_id']);
        });
    }
};

