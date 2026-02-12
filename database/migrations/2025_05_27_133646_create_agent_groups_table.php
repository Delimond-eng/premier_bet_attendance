<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('agent_groups', function (Blueprint $table) {
            $table->id();
            $table->string("libelle"); // ex: "Equipe A - Rotation Nuit"
            $table->unsignedBigInteger("horaire_id")->nullable(); // Horaire par défaut du groupe
            $table->integer("cycle_days")->default(7); // Nombre de jours dans le cycle
            $table->string("status")->default("actif");
            $table->timestamps();

            $table->foreign('horaire_id')->references('id')->on('presence_horaires')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('agent_groups');
    }
};
