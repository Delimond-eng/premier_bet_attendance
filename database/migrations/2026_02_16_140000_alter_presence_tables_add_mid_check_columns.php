<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('presence_agents')) {
            Schema::table('presence_agents', function (Blueprint $table) {
                if (!Schema::hasColumn('presence_agents', 'mid_check')) {
                    $table->timestamp('mid_check')->nullable()->after('started_at');
                }
            });
        }

        if (Schema::hasTable('presence_horaires')) {
            Schema::table('presence_horaires', function (Blueprint $table) {
                if (!Schema::hasColumn('presence_horaires', 'mid_check')) {
                    $table->time('mid_check')->nullable()->after('started_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('presence_agents')) {
            Schema::table('presence_agents', function (Blueprint $table) {
                if (Schema::hasColumn('presence_agents', 'mid_check')) {
                    $table->dropColumn('mid_check');
                }
            });
        }

        if (Schema::hasTable('presence_horaires')) {
            Schema::table('presence_horaires', function (Blueprint $table) {
                if (Schema::hasColumn('presence_horaires', 'mid_check')) {
                    $table->dropColumn('mid_check');
                }
            });
        }
    }
};
