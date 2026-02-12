<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group_planning_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_group_id')->constrained("agent_groups")->cascadeOnDelete(); // Groupe concerné
            $table->foreignId('horaire_id')->nullable()->constrained("presence_horaires")->nullOnDelete(); // Horaire (matin, soir, 24h)
            $table->unsignedTinyInteger('day_index');     // 0 = Lundi, ..., 6 = Dimanche
            $table->boolean('is_rest_day')->default(false);       // Repos ou non
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group_planning_cycles');
    }
};
