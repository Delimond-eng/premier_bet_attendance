<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agent_group_plannings', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('horaire_id')->constrained('sites')->nullOnDelete();
        });

        Schema::table('group_planning_cycles', function (Blueprint $table) {
            $table->foreignId('site_id')->nullable()->after('horaire_id')->constrained('sites')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('group_planning_cycles', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });

        Schema::table('agent_group_plannings', function (Blueprint $table) {
            $table->dropForeign(['site_id']);
            $table->dropColumn('site_id');
        });
    }
};
