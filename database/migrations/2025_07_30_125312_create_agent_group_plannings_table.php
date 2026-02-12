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
        Schema::create('agent_group_plannings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained("agents")->cascadeOnDelete();
            $table->foreignId('agent_group_id')->constrained("agent_groups")->cascadeOnDelete(); // Groupe concerné
            $table->foreignId('horaire_id')->nullable()->constrained("presence_horaires")->nullOnDelete(); 
            $table->date("date");
            $table->boolean("is_rest_day")->default(false);
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
        Schema::dropIfExists('agent_group_plannings');
    }
};
