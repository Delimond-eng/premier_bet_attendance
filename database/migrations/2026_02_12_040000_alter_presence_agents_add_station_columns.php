<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('presence_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('presence_agents', 'station_check_in_id')) {
                $table->unsignedBigInteger('station_check_in_id')->nullable()->after('gps_site_id');
            }

            if (!Schema::hasColumn('presence_agents', 'station_check_out_id')) {
                $table->unsignedBigInteger('station_check_out_id')->nullable()->after('station_check_in_id');
            }

            if (!Schema::hasColumn('presence_agents', 'date_reference')) {
                $table->date('date_reference')->nullable()->after('status');
            }

            $table->index(['agent_id', 'date_reference'], 'presence_agents_agent_date_idx');
            $table->index(['site_id', 'date_reference'], 'presence_agents_site_date_idx');
            $table->index(['station_check_in_id'], 'presence_agents_station_in_idx');
            $table->index(['station_check_out_id'], 'presence_agents_station_out_idx');
        });
    }

    public function down(): void
    {
        Schema::table('presence_agents', function (Blueprint $table) {
            $table->dropIndex('presence_agents_agent_date_idx');
            $table->dropIndex('presence_agents_site_date_idx');
            $table->dropIndex('presence_agents_station_in_idx');
            $table->dropIndex('presence_agents_station_out_idx');

            if (Schema::hasColumn('presence_agents', 'station_check_in_id')) {
                $table->dropColumn('station_check_in_id');
            }
            if (Schema::hasColumn('presence_agents', 'station_check_out_id')) {
                $table->dropColumn('station_check_out_id');
            }
        });
    }
};
